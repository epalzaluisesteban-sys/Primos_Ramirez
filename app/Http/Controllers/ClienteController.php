<?php

namespace App\Http\Controllers;

use App\Support\Almacen;
use App\Models\Abono;
use App\Models\CierreCaja;
use App\Models\Cliente;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Support\Contacto;
use App\Support\Moneda;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClienteController extends Controller
{
    public function index()
    {
        $clientes = $this->clientesConDatos('created_at', 'desc');
        $totalPorCobrar = $clientes->sum('saldo');

        return view('clientes.index', compact('clientes', 'totalPorCobrar'));
    }

    public function create()
    {
        return view('clientes.create');
    }

    // Clientes con conteo de ventas, última compra, total comprado y saldo pendiente.
    private function clientesConDatos(string $orderBy = 'nombre', string $direction = 'asc')
    {
        return Cliente::withCount(['ventas' => fn ($q) => $q->where('anulada', false)])
            ->withMax(['ventas' => fn ($q) => $q->where('anulada', false)], 'fecha')
            ->withSum(['ventas' => fn ($q) => $q->where('anulada', false)], 'total')
            ->orderBy($orderBy, $direction)
            ->get()
            ->map(function ($cliente) {
                $cliente->saldo = $cliente->saldoPendiente();
                $cliente->disponible = $cliente->creditoDisponible($cliente->saldo); // null = sin límite
                return $cliente;
            });
    }

    public function exportarExcel(): StreamedResponse
    {
        $clientes = $this->clientesConDatos();
        return $this->descargarCsv('clientes_' . now()->format('Y-m-d_His') . '.csv', function (callable $fila) use ($clientes) {
            $fila(['Cliente', 'RIF', 'Teléfono', 'Dirección', 'Límite de Crédito', 'Última Compra', 'Total Comprado', 'Saldo Pendiente', 'Crédito Disponible', 'Estado']);

            foreach ($clientes as $c) {
                $fila([
                    $c->nombre,
                    $c->rif,
                    $c->telefono,
                    $c->direccion,
                    $c->limite_credito !== null ? number_format($c->limite_credito, 2, '.', '') : '',
                    $c->ventas_max_fecha ? \Carbon\Carbon::parse($c->ventas_max_fecha)->format('Y-m-d') : '',
                    number_format($c->ventas_sum_total ?? 0, 2, '.', ''),
                    number_format($c->saldo, 2, '.', ''),
                    $c->disponible !== null ? number_format($c->disponible, 2, '.', '') : 'Sin límite',
                    $c->activo ? 'Activo' : 'Inactivo',
                ]);
            }

        });
    }

    public function exportarPDF()
    {
        $clientes = $this->clientesConDatos();
        $totalPorCobrar = $clientes->sum('saldo');

        $pdf = Pdf::loadView('clientes.pdf', compact('clientes', 'totalPorCobrar'));
        return $pdf->download('Clientes_' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Limpia teléfono y cédula/RIF (quita guiones, puntos y espacios que la gente escribe)
     * y los valida: el teléfono solo números (11 dígitos) y el documento tipo + solo números.
     * Devuelve el teléfono y el documento ya listos para guardar.
     */
    private function validarCliente(Request $request, array $extra = []): array
    {
        $request->merge([
            'telefono' => Contacto::limpiarTelefono($request->input('telefono')),
            'documento_numero' => preg_replace('/[\s\.\-]/', '', (string) $request->input('documento_numero')) ?: null,
        ]);

        $request->validate([
            'nombre' => 'required|string|max:255',
            'foto' => 'nullable|image|max:2048',
            'telefono' => Contacto::REGLA_TELEFONO,
            'documento_tipo' => ['nullable', 'required_with:documento_numero', Rule::in(Contacto::TIPOS_DOCUMENTO)],
            'documento_numero' => [
                'nullable',
                'regex:/^\d{6,9}$/',
                function ($atributo, $valor, $falla) use ($request) {
                    if (in_array($request->input('documento_tipo'), ['J', 'G'], true) && strlen($valor) !== 9) {
                        $falla('El RIF de una empresa (J o G) lleva 9 números, incluido el último dígito (ej. 12345678-9).');
                    }
                },
            ],
            'direccion' => 'nullable|string|max:500',
            'limite_credito' => 'nullable|numeric|min:0',
            'notas' => 'nullable|string|max:500',
        ] + $extra);

        return [
            'telefono' => $request->input('telefono'),
            'rif' => Contacto::unirDocumento($request->input('documento_tipo'), $request->input('documento_numero')),
        ];
    }

    public function store(Request $request)
    {
        $contacto = $this->validarCliente($request);

        $datos = $request->only(['nombre', 'direccion', 'limite_credito', 'notas']) + $contacto;
        if ($request->hasFile('foto')) {
            $datos['foto'] = Almacen::guardar($request->file('foto'), 'clientes');
        }

        Cliente::create($datos);

        return redirect()->route('clientes.index')->with('success', 'Cliente registrado exitosamente.');
    }

    /**
     * Alta desde la ventana de "Nueva venta" (todos los roles): solo nombre, teléfono y cédula/RIF.
     * Responde JSON para que el cliente quede elegido en la venta sin recargar la página.
     */
    public function registroRapido(Request $request)
    {
        $contacto = $this->validarCliente($request);

        if ($contacto['rif'] && $existente = Cliente::where('rif', $contacto['rif'])->first()) {
            throw ValidationException::withMessages([
                'documento_numero' => "Ya hay un cliente con esa cédula o RIF: {$existente->nombre}."
                    . ($existente->activo ? ' Elígelo en la lista.' : ' Está desactivado; pide a un encargado que lo active en Clientes.'),
            ]);
        }

        $cliente = Cliente::create(['nombre' => $request->input('nombre')] + $contacto);

        return response()->json(['id' => $cliente->id, 'nombre' => $cliente->nombre], 201);
    }

    public function edit(Cliente $cliente)
    {
        return view('clientes.edit', compact('cliente'));
    }

    public function update(Request $request, Cliente $cliente)
    {
        $contacto = $this->validarCliente($request, [
            'activo' => 'nullable|boolean',
        ]);

        $datos = [
            'nombre' => $request->nombre,
            'telefono' => $contacto['telefono'],
            'rif' => $contacto['rif'],
            'direccion' => $request->direccion,
            'limite_credito' => $request->limite_credito,
            'notas' => $request->notas,
            'activo' => $request->boolean('activo'),
        ];

        if ($request->hasFile('foto')) {
            if ($cliente->foto) {
                Almacen::borrar($cliente->foto);
            }
            $datos['foto'] = Almacen::guardar($request->file('foto'), 'clientes');
        }

        $cliente->update($datos);

        return redirect()->route('clientes.index')->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Cliente $cliente)
    {
        if ($cliente->ventas()->count() > 0) {
            return back()->withErrors(['error' => 'No se puede eliminar: este cliente tiene ventas registradas. Puedes marcarlo como inactivo en su lugar.']);
        }

        if ($cliente->foto) {
            Almacen::borrar($cliente->foto);
        }

        $cliente->delete();
        return redirect()->route('clientes.index')->with('success', 'Cliente eliminado.');
    }

    // Muestra el detalle de cuenta corriente de un cliente: ventas a crédito + abonos
    public function show(Cliente $cliente)
    {
        $cliente->load(['ventas' => function ($q) {
            $q->where('tipo_pago', 'credito')->with('detalles.producto')->latest();
        }, 'abonos' => function ($q) {
            $q->latest('fecha');
        }]);

        $saldo = $cliente->saldoPendiente();
        $disponible = $cliente->creditoDisponible($saldo);
        $totalComprado = $cliente->totalComprado();
        $ultimaCompra = $cliente->ultimaCompra();

        // Estado de la caja de hoy: los abonos solo se reciben con la caja abierta
        $estadoCaja = CierreCaja::estaCerrada(now()) ? 'cerrada' : (CierreCaja::estaAbierta(now()) ? 'abierta' : 'sin_abrir');

        return view('clientes.show', compact('cliente', 'saldo', 'disponible', 'totalComprado', 'ultimaCompra', 'estadoCaja'));
    }

    // Registrar un abono (pago) que reduce la deuda del cliente
    public function registrarAbono(Request $request, Cliente $cliente)
    {
        $request->validate([
            'monto' => 'required|numeric|min:0.01',
            'moneda' => 'nullable|in:USD,VES',
            'notas' => 'nullable|string|max:500',
        ]);

        // Un abono es efectivo que entra a la caja del día: después del cierre ya no saldría en el cuadre
        if (CierreCaja::estaCerrada(now())) {
            return back()->withErrors(['error' => 'La caja de hoy ya fue cerrada. Para recibir el abono, un encargado puede reabrirla en "Caja" (botón "Reabrir caja").'])->withInput();
        }
        // Y tiene que haber una caja abierta hoy, para que el abono entre en un cuadre con su fondo inicial
        if (!CierreCaja::estaAbierta(now())) {
            return back()->withErrors(['error' => 'Primero abre la caja del día (menú "Caja", botón "Abrir Caja del Día"). Después podrás registrar el abono.'])->withInput();
        }

        $moneda = $request->moneda === 'VES' ? 'VES' : 'USD';
        $tasa = Moneda::tasa();
        $montoMoneda = round((float) $request->monto, 2);

        if ($moneda === 'VES' && $tasa <= 0) {
            return back()->withErrors(['error' => 'No hay tasa del BCV cargada, no se puede recibir un abono en bolívares.'])->withInput();
        }

        // La deuda se lleva en USD: un abono en bolívares se convierte con la tasa BCV del día
        $montoUsd = $moneda === 'VES' ? Moneda::aUsd($montoMoneda, $tasa) : $montoMoneda;
        $saldoActual = $cliente->saldoPendiente();

        if ($montoUsd > $saldoActual + 0.01) {
            return back()->withErrors(['error' => 'El abono (' . Moneda::usd($montoUsd) . ') no puede ser mayor al saldo pendiente (' . Moneda::usd($saldoActual) . ').'])->withInput();
        }

        Abono::create([
            'cliente_id' => $cliente->id,
            'user_id' => auth()->id(),
            'monto' => min($montoUsd, max($saldoActual, 0.01)),
            'moneda' => $moneda,
            'monto_moneda' => $montoMoneda,
            'tasa_cambio' => $tasa > 0 ? $tasa : null,
            'notas' => $request->notas,
            'fecha' => now(),
        ]);

        return redirect()->route('clientes.show', $cliente->id)->with('success', 'Abono registrado. Saldo actualizado.');
    }
}
