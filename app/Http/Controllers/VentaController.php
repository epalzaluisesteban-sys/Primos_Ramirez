<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\Producto;
use App\Models\DetalleVenta;
use App\Models\Cliente;
use App\Support\Moneda;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VentaController extends Controller
{
    public function index(Request $request)
    {
        $ventas = $this->ventasFiltradas($request);

        // Suma total de las ventas filtradas (no cuenta las anuladas)
        $totalPeriodo = $ventas->where('anulada', false)->sum('total');

        $totalPeriodoVes = $ventas->where('anulada', false)->sum(fn ($v) => $v->total_ves ?? Moneda::aVes($v->total));

        return view('ventas.index', compact('ventas', 'totalPeriodo', 'totalPeriodoVes'));
    }

    public function create()
    {
        $productos = Producto::where('stock', '>', 0)->get();
        $clientes = Cliente::where('activo', true)->orderBy('nombre')->get();
        return view('ventas.create', compact('productos', 'clientes'));
    }

    public function store(Request $request)
    {
        $this->validarVenta($request);

        DB::beginTransaction();

        try {
            $pago = $this->datosPago($request);
            $venta = Venta::create($pago + [
                'user_id' => auth()->id(),
                'fecha' => now(),
                'total' => 0,
            ]);

            $total = $this->registrarLineas($venta, $request->productos);
            $this->verificarLimiteCredito($pago['cliente_id'], $total);
            $venta->update(['total' => $total] + $this->montosEnBolivares($total));

            DB::commit();

            return redirect()->route('ventas.index')->with('success', 'Venta registrada con éxito.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Formulario para editar una venta reciente (mismo día, no anulada).
     */
    public function edit(Venta $venta)
    {
        if (!$venta->puedeEditarse()) {
            return redirect()->route('ventas.index')->withErrors(['error' => 'Solo se pueden editar ventas del día que aún no han sido anuladas.']);
        }

        $venta->load('detalles.producto');
        $productos = Producto::where('stock', '>', 0)
            ->orWhereIn('id', $venta->detalles->pluck('producto_id'))
            ->get();
        $clientes = Cliente::where('activo', true)->orderBy('nombre')->get();

        return view('ventas.edit', compact('venta', 'productos', 'clientes'));
    }

    /**
     * Actualiza una venta reciente. Devuelve el stock de las líneas
     * anteriores y vuelve a descontarlo según los productos/cantidades
     * enviados, validando disponibilidad antes de confirmar.
     */
    public function update(Request $request, Venta $venta)
    {
        if (!$venta->puedeEditarse()) {
            return redirect()->route('ventas.index')->withErrors(['error' => 'Solo se pueden editar ventas del día que aún no han sido anuladas.']);
        }

        $this->validarVenta($request);

        DB::beginTransaction();

        try {
            $this->devolverStock($venta);
            $venta->detalles()->delete();

            $pago = $this->datosPago($request);
            $total = $this->registrarLineas($venta, $request->productos);
            $this->verificarLimiteCredito($pago['cliente_id'], $total, $venta);

            $venta->update($pago + ['total' => $total, 'editada' => true] + $this->montosEnBolivares($total));

            DB::commit();

            return redirect()->route('ventas.index')->with('success', 'Venta #' . $venta->id . ' actualizada correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Anula una venta reciente: devuelve el stock de cada línea al
     * inventario y marca la venta como anulada (no se elimina, para
     * conservar el historial y la numeración de transacciones).
     */
    public function anular(Request $request, Venta $venta)
    {
        if (!$venta->puedeEditarse()) {
            return redirect()->route('ventas.index')->withErrors(['error' => 'Solo se pueden anular ventas del día que aún no han sido anuladas.']);
        }

        $request->validate([
            'motivo_anulacion' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $this->devolverStock($venta);

            $venta->update([
                'anulada' => true,
                'anulada_at' => now(),
                'motivo_anulacion' => $request->motivo_anulacion,
            ]);

            DB::commit();

            return redirect()->route('ventas.index')->with('success', 'Venta #' . $venta->id . ' anulada. El stock fue devuelto al inventario.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function descargarPDF(Venta $venta)
    {
        $venta->load(['usuario', 'detalles.producto']);
        $pdf = Pdf::loadView('ventas.pdf', compact('venta'));
        return $pdf->download('Comprobante_Venta_#' . $venta->id . '.pdf');
    }

    /**
     * Ticket imprimible en formato angosto (impresora térmica 80mm),
     * como alternativa rápida al comprobante PDF de tamaño carta.
     */
    public function ticket(Venta $venta)
    {
        $venta->load(['usuario', 'cliente', 'detalles.producto']);
        return view('ventas.ticket', compact('venta'));
    }

    /**
     * Exporta el historial de ventas (respetando los filtros de fecha
     * usados en el listado) a un archivo CSV compatible con Excel.
     */
    public function exportarExcel(Request $request): StreamedResponse
    {
        $ventas = $this->ventasFiltradas($request);
        return $this->descargarCsv('ventas_' . now()->format('Y-m-d_His') . '.csv', function (callable $fila) use ($ventas) {
            $fila(['N° Transaccion', 'Fecha', 'Atendido Por', 'Tipo de Pago', 'Cliente', 'Productos', 'Estado', 'Total $', 'Total Bs.', 'Tasa BCV', 'Cobrado en']);

            foreach ($ventas as $v) {
                $productos = $v->detalles->map(function ($d) {
                    return ($d->producto->nombre ?? 'Producto eliminado') . ' (x' . $d->cantidad . ')';
                })->implode(' | ');

                $fila([
                    $v->id,
                    optional($v->fecha)->format('Y-m-d H:i'),
                    $v->usuario->name ?? 'Sistema',
                    $v->tipo_pago === 'credito' ? 'Fiado' : 'Contado',
                    $v->cliente->nombre ?? '',
                    $productos,
                    $v->anulada ? 'Anulada' : 'Activa',
                    number_format($v->total, 2, '.', ''),
                    $v->total_ves !== null ? number_format($v->total_ves, 2, '.', '') : '',
                    $v->tasa_cambio !== null ? number_format($v->tasa_cambio, 4, '.', '') : '',
                    $v->tipo_pago === 'credito' ? 'Crédito' : ($v->moneda_pago === 'VES' ? 'Bolívares' : 'Dólares'),
                ]);
            }

        });
    }

    private function ventasFiltradas(Request $request)
    {
        return Venta::with(['usuario', 'cliente', 'detalles.producto'])
            ->when($request->filled('fecha_inicio'), fn ($q) => $q->whereDate('created_at', '>=', $request->fecha_inicio))
            ->when($request->filled('fecha_fin'), fn ($q) => $q->whereDate('created_at', '<=', $request->fecha_fin))
            ->latest()
            ->get();
    }

    private function validarVenta(Request $request): void
    {
        $request->validate([
            'productos' => 'required|array|min:1',
            'productos.*.id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|numeric|min:0.001',
            'tipo_pago' => 'nullable|in:contado,credito',
            'moneda_pago' => 'nullable|in:USD,VES',
            'cliente_id' => 'nullable|exists:clientes,id',
        ]);

        if ($request->tipo_pago === 'credito' && !$request->cliente_id) {
            throw ValidationException::withMessages([
                'error' => 'Para una venta a crédito (fiado) debes seleccionar un cliente registrado.',
            ]);
        }
    }

    private function datosPago(Request $request): array
    {
        $credito = $request->tipo_pago === 'credito';
        $moneda = (!$credito && $request->moneda_pago === 'VES') ? 'VES' : 'USD';

        if ($moneda === 'VES' && Moneda::tasa() <= 0) {
            throw ValidationException::withMessages([
                'error' => 'No hay tasa del BCV cargada, no se puede cobrar en bolívares. Registra la tasa en "Tasa de cambio".',
            ]);
        }

        return [
            'cliente_id' => $credito ? $request->cliente_id : null,
            'tipo_pago' => $request->tipo_pago ?? 'contado',
            'moneda_pago' => $moneda,
        ];
    }

    // Tasa BCV con la que queda registrada la venta, y su total en bolívares.
    private function montosEnBolivares(float $totalUsd): array
    {
        $tasa = Moneda::tasa();

        return [
            'tasa_cambio' => $tasa > 0 ? $tasa : null,
            'total_ves' => $tasa > 0 ? Moneda::aVes($totalUsd, $tasa) : null,
        ];
    }

    /**
     * Crea las líneas de la venta, descuenta el stock y devuelve el total.
     * Lanza excepción si algún producto no tiene stock suficiente.
     */
    private function registrarLineas(Venta $venta, array $items): float
    {
        $total = 0.0;

        foreach ($items as $item) {
            $producto = Producto::lockForUpdate()->findOrFail($item['id']);

            if ($producto->stock < $item['cantidad']) {
                throw new \Exception("Stock insuficiente para el producto: {$producto->nombre}");
            }

            $subtotal = round($producto->precio * $item['cantidad'], 2);
            $total += $subtotal;

            DetalleVenta::create([
                'venta_id' => $venta->id,
                'producto_id' => $producto->id,
                'cantidad' => $item['cantidad'],
                'precio_unitario' => $producto->precio,
                'subtotal' => $subtotal,
            ]);

            $producto->decrement('stock', $item['cantidad']);
        }

        return round($total, 2);
    }

    /**
     * Impide que una venta a crédito deje al cliente por encima de su límite
     * (si tiene uno). Al editar, el monto anterior de la propia venta no se cuenta dos veces.
     */
    private function verificarLimiteCredito(?int $clienteId, float $total, ?Venta $ventaActual = null): void
    {
        $cliente = $clienteId ? Cliente::find($clienteId) : null;

        if (!$cliente || $cliente->limite_credito === null) {
            return;
        }

        $saldo = $cliente->saldoPendiente();
        if ($ventaActual && $ventaActual->tipo_pago === 'credito' && $ventaActual->cliente_id === $cliente->id) {
            $saldo -= (float) $ventaActual->total;
        }

        if ($saldo + $total > (float) $cliente->limite_credito) {
            throw new \Exception(sprintf(
                'Esta venta supera el límite de crédito de %s: debe $%s, la venta es de $%s y el límite es $%s.',
                $cliente->nombre,
                number_format($saldo, 2),
                number_format($total, 2),
                number_format($cliente->limite_credito, 2)
            ));
        }
    }

    // Devuelve al inventario las cantidades de todas las líneas de la venta.
    private function devolverStock(Venta $venta): void
    {
        $venta->load('detalles');

        foreach ($venta->detalles as $detalle) {
            Producto::whereKey($detalle->producto_id)->increment('stock', $detalle->cantidad);
        }
    }
}
