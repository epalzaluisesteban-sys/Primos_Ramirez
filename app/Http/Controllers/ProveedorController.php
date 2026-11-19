<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\CierreCaja;
use App\Models\Proveedor;
use App\Support\Contacto;
use App\Support\Moneda;
use App\Support\Unicidad;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProveedorController extends Controller
{
    public function index()
    {
        $proveedores = Proveedor::withCount('compras')
            ->withSum(['compras as comprado_credito' => fn ($q) => $q->where('tipo_pago', 'credito')], 'total')
            ->withSum('pagos as pagado', 'monto')
            ->with('categorias')
            ->latest()
            ->get();
        // Para el formulario de "Nuevo proveedor" en la ventana flotante
        $categorias = Categoria::orderBy('nombre')->get();

        return view('proveedores.index', compact('proveedores', 'categorias'));
    }

    public function create()
    {
        $categorias = Categoria::orderBy('nombre')->get();
        return view('proveedores.create', compact('categorias'));
    }

    /**
     * Limpia teléfonos y RIF y valida. Ni el nombre (sin importar mayúsculas ni tildes) ni el RIF se repiten,
     * tampoco con un proveedor desactivado. Devuelve los datos listos para guardar.
     */
    private function validarProveedor(Request $request, ?Proveedor $proveedor = null): array
    {
        $request->merge([
            'telefono' => Contacto::limpiarTelefono($request->input('telefono')),
            'whatsapp' => Contacto::limpiarTelefono($request->input('whatsapp')),
            'documento_numero' => Contacto::limpiarNumeroDocumento($request->input('documento_numero')),
        ]);

        $otroDueno = function (string $rif) use ($proveedor) {
            $otro = Proveedor::where('rif', $rif)->when($proveedor, fn ($q) => $q->whereKeyNot($proveedor->id))->first();

            return $otro ? "Ya hay un proveedor con ese RIF: {$otro->nombre}." . $otro->avisoSiInactivo() : null;
        };

        $request->validate([
            'nombre' => [
                'required', 'string', 'min:2', 'max:255',
                function ($atributo, $valor, $falla) use ($proveedor) {
                    if ($otro = Proveedor::conClave(Unicidad::normalizar($valor), $proveedor?->id)) {
                        $falla("Ya hay un proveedor llamado «{$otro->nombre}»." . $otro->avisoSiInactivo());
                    }
                },
            ],
            'contacto' => 'nullable|string|max:255',
            'telefono' => Contacto::REGLA_TELEFONO,
            'whatsapp' => Contacto::REGLA_TELEFONO,
            'email' => 'nullable|email|max:255',
            'direccion' => 'nullable|string|max:500',
            'notas' => 'nullable|string|max:500',
            'activo' => 'nullable|boolean',
            'categorias' => 'nullable|array',
            'categorias.*' => 'integer|exists:categorias,id',
            'da_credito' => 'nullable|boolean',
            'dias_credito' => 'nullable|integer|min:1|max:365',
        ] + Contacto::reglasDocumento($otroDueno), [
            'categorias.*.exists' => 'Una de las categorías elegidas ya no existe. Recarga la página.',
            'dias_credito.min' => 'Los días de crédito van de 1 a 365.',
            'dias_credito.max' => 'Los días de crédito van de 1 a 365.',
        ]);

        $daCredito = $request->boolean('da_credito');

        return $request->only(['nombre', 'contacto', 'telefono', 'whatsapp', 'email', 'direccion', 'notas']) + [
            'rif' => Contacto::unirDocumento($request->input('documento_tipo'), $request->input('documento_numero')),
            'da_credito' => $daCredito,
            'dias_credito' => $daCredito ? $request->input('dias_credito') : null,
        ];
    }

    public function store(Request $request)
    {
        $datos = $this->validarProveedor($request);

        DB::transaction(function () use ($datos, $request) {
            Proveedor::create($datos)->categorias()->sync($request->input('categorias', []));
        });

        return redirect()->route('proveedores.index')->with('success', 'Proveedor registrado exitosamente.');
    }

    public function edit(Proveedor $proveedor)
    {
        $categorias = Categoria::orderBy('nombre')->get();
        return view('proveedores.edit', compact('proveedor', 'categorias'));
    }

    public function update(Request $request, Proveedor $proveedor)
    {
        $datos = $this->validarProveedor($request, $proveedor);

        DB::transaction(function () use ($proveedor, $datos, $request) {
            $proveedor->update($datos + ['activo' => $request->boolean('activo')]);
            $proveedor->categorias()->sync($request->input('categorias', []));
        });

        return redirect()->route('proveedores.index')->with('success', 'Proveedor actualizado correctamente.');
    }

    public function destroy(Proveedor $proveedor)
    {
        if ($proveedor->compras()->count() > 0) {
            return back()->withErrors(['error' => 'No se puede eliminar: este proveedor tiene compras registradas. Puedes marcarlo como inactivo en su lugar.']);
        }

        $proveedor->delete();
        return redirect()->route('proveedores.index')->with('success', 'Proveedor eliminado.');
    }

    /**
     * Activa o desactiva un proveedor desde la tabla. Es la forma de "quitarlo" cuando
     * ya tiene compras (no se puede borrar): deja de aparecer para compras nuevas.
     */
    public function alternarActivo(Proveedor $proveedor)
    {
        $proveedor->update(['activo' => !$proveedor->activo]);

        return back()->with('success', $proveedor->activo
            ? "{$proveedor->nombre} vuelve a estar disponible para compras."
            : "{$proveedor->nombre} quedó inactivo: no aparecerá en compras nuevas. Su historial se conserva.");
    }

    /**
     * Ficha del proveedor: lo que surte, lo que se le debe, sus compras y los pagos que se le hicieron.
     * El listado la pide por fetch y muestra un resumen en la ventana flotante.
     */
    public function show(Request $request, Proveedor $proveedor)
    {
        $proveedor->load('categorias');
        $compras = $proveedor->compras()->with(['producto', 'usuario'])->latest('fecha')->get();
        $pagos = $proveedor->pagos()->with('usuario')->latest('fecha')->get();
        $deuda = $proveedor->estadoDeuda();
        $totalComprado = $proveedor->totalComprado();
        $ultimaCompra = $proveedor->ultimaCompra();

        if ($request->ajax()) {
            return view('proveedores._ficha', compact('proveedor', 'compras', 'pagos', 'deuda', 'totalComprado', 'ultimaCompra'));
        }

        $cajaAbierta = !CierreCaja::pendienteAnterior() && CierreCaja::estaAbierta(now());

        return view('proveedores.show', compact('proveedor', 'compras', 'pagos', 'deuda', 'totalComprado', 'ultimaCompra', 'cajaAbierta'));
    }

    /** Lo que se les debe a los proveedores: compras a crédito menos pagos, con el próximo vencimiento. */
    public function deudas()
    {
        return view('proveedores.deudas', $this->datosDeudas());
    }

    public function deudasPdf()
    {
        $pdf = Pdf::loadView('proveedores.deudas-pdf', $this->datosDeudas());

        return $this->entregarPdf($pdf, 'Deudas_proveedores_' . now()->format('Y-m-d') . '.pdf');
    }

    private function datosDeudas(): array
    {
        $deudas = Proveedor::whereHas('compras', fn ($q) => $q->where('tipo_pago', 'credito'))
            ->orderBy('nombre')
            ->get()
            ->map(fn ($proveedor) => ['proveedor' => $proveedor] + $proveedor->estadoDeuda())
            ->filter(fn ($fila) => $fila['saldo'] > 0)
            ->sortBy(fn ($fila) => $fila['vence_el']?->timestamp ?? PHP_INT_MAX)
            ->values();

        return [
            'deudas' => $deudas,
            'totalDeuda' => round($deudas->sum('saldo'), 2),
            'totalVencido' => round($deudas->where('vencido', true)->sum('saldo'), 2),
            'tasa' => Moneda::tasa(),
        ];
    }
}
