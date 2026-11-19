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
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        return view('clientes.index', $this->datosClientes($request, 'created_at', 'desc'));
    }

    // Filtro de la lista, el CSV y el PDF (?estado=...)
    public const ESTADOS = ['todos' => 'Todos', 'con_deuda' => 'Con deuda', 'sin_deuda' => 'Sin deuda'];

    /**
     * Clientes según el filtro: con deuda (saldo > 0, primero los que más deben, con la fecha del fiado más viejo
     * sin pagar) o sin deuda. Pantalla, CSV y PDF usan lo mismo.
     */
    private function datosClientes(Request $request, string $orderBy = 'nombre', string $direction = 'asc'): array
    {
        $estado = array_key_exists((string) $request->query('estado'), self::ESTADOS) ? $request->query('estado') : 'todos';
        $todos = $this->clientesConDatos($orderBy, $direction);
        $deben = fn ($c) => $c->saldo > 0.005;

        $clientes = match ($estado) {
            'con_deuda' => $todos->filter($deben)
                ->each(fn ($c) => $c->deuda_desde = $c->deudaDesde())
                ->sortByDesc('saldo')
                ->values(),
            'sin_deuda' => $todos->reject($deben)->values(),
            default => $todos,
        };

        return [
            'clientes' => $clientes,
            'estado' => $estado,
            'conteos' => ['todos' => $todos->count(), 'con_deuda' => $todos->filter($deben)->count(), 'sin_deuda' => $todos->reject($deben)->count()],
            'totalPorCobrar' => $todos->sum('saldo'),
        ];
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

    public function exportarExcel(Request $request): StreamedResponse
    {
        ['clientes' => $clientes, 'estado' => $estado] = $this->datosClientes($request);
        $sufijo = $estado === 'todos' ? '' : '_' . $estado;
        return $this->descargarCsv('clientes' . $sufijo . '_' . now()->format('Y-m-d_His') . '.csv', function (callable $fila) use ($clientes) {
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

    public function exportarPDF(Request $request)
    {
        $datos = $this->datosClientes($request);
        $nombre = ['todos' => 'Clientes', 'con_deuda' => 'Clientes_con_deuda', 'sin_deuda' => 'Clientes_sin_deuda'][$datos['estado']];

        $pdf = Pdf::loadView('clientes.pdf', $datos);
        return $this->entregarPdf($pdf, $nombre . '_' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Limpia teléfono y cédula/RIF (quita guiones, puntos y espacios que la gente escribe)
     * y los valida: el teléfono solo números (11 dígitos) y el documento tipo + solo números.
     * Devuelve el teléfono y el documento ya listos para guardar.
     */
    private function validarCliente(Request $request, ?Cliente $cliente = null, array $extra = []): array
    {
        $request->merge([
            'nombre' => preg_replace('/\s+/', ' ', trim((string) $request->input('nombre'))),
            'telefono' => Contacto::limpiarTelefono($request->input('telefono')),
            'documento_numero' => Contacto::limpiarNumeroDocumento($request->input('documento_numero')),
        ]);

        // Dos clientes no pueden tener la misma cédula/RIF, tampoco si uno está desactivado
        $otroDueno = function (string $rif) use ($cliente) {
            $otro = Cliente::where('rif', $rif)->when($cliente, fn ($q) => $q->whereKeyNot($cliente->id))->first();

            return $otro
                ? "Ya hay un cliente con esa cédula o RIF: {$otro->nombre}."
                    . ($otro->activo ? ' Búscalo en la lista en vez de registrarlo de nuevo.' : ' Está desactivado; un encargado puede activarlo en Clientes.')
                : null;
        };

        $request->validate([
            'nombre' => 'required|string|min:2|max:255',
            'foto' => 'nullable|image|max:2048',
            'telefono' => Contacto::REGLA_TELEFONO,
            'direccion' => 'nullable|string|max:500',
            'limite_credito' => 'nullable|numeric|min:0|max:99999999',
            'notas' => 'nullable|string|max:500',
        ] + Contacto::reglasDocumento($otroDueno) + $extra);

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

        $cliente = Cliente::create(['nombre' => $request->input('nombre')] + $contacto);

        return response()->json(['id' => $cliente->id, 'nombre' => $cliente->nombre], 201);
    }

    public function edit(Cliente $cliente)
    {
        return view('clientes.edit', compact('cliente'));
    }

    public function update(Request $request, Cliente $cliente)
    {
        $contacto = $this->validarCliente($request, $cliente, [
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

    /**
     * Cuenta corriente del cliente: ventas a crédito + abonos. El listado la pide por fetch y muestra
     * un resumen en la ventana flotante; abierta directo es la página donde se cobra el abono.
     */
    public function show(Request $request, Cliente $cliente)
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

        if ($request->ajax()) {
            return view('clientes._ficha', compact('cliente', 'saldo', 'disponible', 'totalComprado', 'ultimaCompra'));
        }

        // Estado de la caja de hoy: los abonos solo se reciben con la caja abierta
        $estadoCaja = match (true) {
            CierreCaja::pendienteAnterior() !== null => 'pendiente',
            CierreCaja::estaCerrada(now()) => 'cerrada',
            CierreCaja::estaAbierta(now()) => 'abierta',
            default => 'sin_abrir',
        };

        return view('clientes.show', compact('cliente', 'saldo', 'disponible', 'totalComprado', 'ultimaCompra', 'estadoCaja'));
    }

    // Registrar un abono (pago) que reduce la deuda del cliente
    public function registrarAbono(Request $request, Cliente $cliente)
    {
        $request->validate([
            'monto' => 'required|numeric|min:0.01|max:9999999999',
            'moneda' => 'nullable|in:USD,VES',
            'notas' => 'nullable|string|max:500',
        ]);

        if ($aviso = CierreCaja::avisoCajaPendiente()) {
            return back()->withErrors(['error' => $aviso])->withInput();
        }
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
