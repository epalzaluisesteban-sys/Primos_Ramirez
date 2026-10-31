<?php

namespace App\Http\Controllers;

use App\Support\Almacen;
use App\Models\Abono;
use App\Models\Cliente;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Support\Moneda;
use Illuminate\Http\Request;
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
                return $cliente;
            });
    }

    public function exportarExcel(): StreamedResponse
    {
        $clientes = $this->clientesConDatos();
        return $this->descargarCsv('clientes_' . now()->format('Y-m-d_His') . '.csv', function (callable $fila) use ($clientes) {
            $fila(['Cliente', 'RIF', 'Teléfono', 'Dirección', 'Límite de Crédito', 'Última Compra', 'Total Comprado', 'Saldo Pendiente', 'Estado']);

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

    private function validacionCliente(): array
    {
        return [
            'nombre' => 'required|string|max:255',
            'foto' => 'nullable|image|max:2048',
            'telefono' => 'nullable|string|max:50',
            'rif' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:500',
            'limite_credito' => 'nullable|numeric|min:0',
            'notas' => 'nullable|string|max:500',
        ];
    }

    public function store(Request $request)
    {
        $request->validate($this->validacionCliente());

        $datos = $request->except('foto');
        if ($request->hasFile('foto')) {
            $datos['foto'] = Almacen::guardar($request->file('foto'), 'clientes');
        }

        Cliente::create($datos);

        return redirect()->route('clientes.index')->with('success', 'Cliente registrado exitosamente.');
    }

    public function edit(Cliente $cliente)
    {
        return view('clientes.edit', compact('cliente'));
    }

    public function update(Request $request, Cliente $cliente)
    {
        $request->validate($this->validacionCliente() + [
            'activo' => 'nullable|boolean',
        ]);

        $datos = [
            'nombre' => $request->nombre,
            'telefono' => $request->telefono,
            'rif' => $request->rif,
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
        $totalComprado = $cliente->totalComprado();
        $ultimaCompra = $cliente->ultimaCompra();

        return view('clientes.show', compact('cliente', 'saldo', 'totalComprado', 'ultimaCompra'));
    }

    // Registrar un abono (pago) que reduce la deuda del cliente
    public function registrarAbono(Request $request, Cliente $cliente)
    {
        $request->validate([
            'monto' => 'required|numeric|min:0.01',
            'moneda' => 'nullable|in:USD,VES',
            'notas' => 'nullable|string|max:500',
        ]);

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
