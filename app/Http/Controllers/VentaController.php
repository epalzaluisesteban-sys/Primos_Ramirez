<?php

namespace App\Http\Controllers;

use App\Models\CierreCaja;
use App\Models\Venta;
use App\Models\Producto;
use App\Models\DetalleVenta;
use App\Models\Cliente;
use App\Support\Cantidad;
use App\Support\Moneda;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VentaController extends Controller
{
    public function index(Request $request)
    {
        $ventas = $this->ventasFiltradas($request);

        $totalPeriodo = $ventas->sum('total');
        $totalPeriodoVes = $ventas->sum(fn ($v) => $v->total_ves ?? Moneda::aVes($v->total));
        $enPapelera = Venta::where('anulada', true)->count();

        return view('ventas.index', compact('ventas', 'totalPeriodo', 'totalPeriodoVes', 'enPapelera'));
    }

    /**
     * Detalle de una venta. El listado lo pide por fetch y lo muestra en la ventana flotante;
     * abierto directo (sin JS o en otra pestaña) se ve como página.
     */
    public function show(Request $request, Venta $venta)
    {
        // Las ventas de la papelera solo las ven quienes pueden eliminarlas o restaurarlas
        if ($venta->anulada && !$request->user()->esEncargadoOSuperior()) {
            abort(403);
        }

        $venta->load(['usuario', 'cliente', 'eliminadaPor', 'detalles.producto']);

        if ($request->ajax()) {
            return view('ventas._detalle', compact('venta'));
        }

        return view('ventas.show', compact('venta'));
    }

    // Ventas eliminadas: no cuentan en caja, reportes, kardex ni saldo de clientes.
    public function papelera()
    {
        $ventas = Venta::with(['usuario', 'cliente', 'eliminadaPor', 'detalles'])
            ->where('anulada', true)
            ->latest('anulada_at')
            ->get();

        return view('ventas.papelera', compact('ventas'));
    }

    // Mensaje cuando la caja del día ya se cerró: lo que entre después no saldría en el cuadre.
    private const CAJA_CERRADA = 'La caja de hoy ya fue cerrada. Si se cerró por error, un encargado puede reabrirla en "Caja" (botón "Reabrir caja") y después se registra la venta.';

    // Mensaje cuando todavía no se abrió la caja del día: la venta no tendría cuadre.
    private const CAJA_SIN_ABRIR = 'Primero abre la caja del día: escribe el fondo inicial y pulsa "Abrir Caja del Día". Después podrás registrar ventas.';

    private const NO_EDITABLE = 'Solo se pueden editar o eliminar ventas de hoy y mientras la caja de hoy siga abierta.';

    private const NO_RESTAURABLE = 'Solo se puede restaurar una venta de hoy y con la caja de hoy abierta. Las de otros días se quedan en la papelera porque su caja ya se cuadró.';

    public function create()
    {
        if (CierreCaja::estaCerrada(now())) {
            return redirect()->route('ventas.index')->withErrors(['error' => self::CAJA_CERRADA]);
        }
        if (!CierreCaja::estaAbierta(now())) {
            // A la pantalla de caja, donde se abre (todos los roles pueden hacerlo)
            return redirect()->route('caja.index')->withErrors(['error' => self::CAJA_SIN_ABRIR]);
        }

        $productos = Producto::activos()->where('stock', '>', 0)->orderBy('nombre')->get();
        $clientes = Cliente::where('activo', true)->orderBy('nombre')->get();
        return view('ventas.create', compact('productos', 'clientes'));
    }

    public function store(Request $request)
    {
        if (CierreCaja::estaCerrada(now())) {
            return back()->withErrors(['error' => self::CAJA_CERRADA])->withInput();
        }
        if (!CierreCaja::estaAbierta(now())) {
            return redirect()->route('caja.index')->withErrors(['error' => self::CAJA_SIN_ABRIR]);
        }

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
            // withInput: el vendedor no pierde los productos ni el cliente que ya había cargado
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Formulario para editar una venta reciente (mismo día, no anulada).
     */
    public function edit(Venta $venta)
    {
        if (!$venta->puedeEditarse()) {
            return redirect()->route('ventas.index')->withErrors(['error' => self::NO_EDITABLE]);
        }

        $venta->load('detalles.producto');
        $productos = Producto::where(fn ($q) => $q->where('activo', true)->where('stock', '>', 0))
            ->orWhereIn('id', $venta->detalles->pluck('producto_id'))
            ->orderBy('nombre')
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
            return redirect()->route('ventas.index')->withErrors(['error' => self::NO_EDITABLE]);
        }

        $this->validarVenta($request);

        DB::beginTransaction();

        try {
            // Los productos que ya estaban en la venta se pueden conservar aunque luego se hayan desactivado
            $productosPrevios = $venta->detalles()->pluck('producto_id')->all();

            $this->devolverStock($venta);
            $venta->detalles()->delete();

            $pago = $this->datosPago($request);
            $total = $this->registrarLineas($venta, $request->productos, $productosPrevios);
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
     * Manda una venta de hoy a la papelera: devuelve el stock y la marca como anulada, así deja de
     * contar en caja, reportes, kardex y saldo del cliente. No se borra de la base de datos.
     */
    public function anular(Request $request, Venta $venta)
    {
        if (!$venta->puedeEditarse()) {
            return redirect()->route('ventas.index')->withErrors(['error' => self::NO_EDITABLE]);
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
                'anulada_por' => $request->user()->id,
            ]);

            DB::commit();

            return redirect()->route('ventas.index')->with('success', 'Venta #' . $venta->id . ' enviada a la papelera. La mercancía volvió al inventario.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Saca una venta de la papelera: vuelve a descontar el stock (si todavía alcanza) y a contar
     * en la caja de hoy y, si es fiada, en el saldo del cliente.
     */
    public function restaurar(Venta $venta)
    {
        if (!$venta->puedeRestaurarse()) {
            return redirect()->route('ventas.papelera')->withErrors(['error' => self::NO_RESTAURABLE]);
        }

        DB::beginTransaction();

        try {
            $venta->load('detalles');

            foreach ($venta->detalles as $detalle) {
                $producto = Producto::lockForUpdate()->findOrFail($detalle->producto_id);

                if ($producto->stock < $detalle->cantidad) {
                    throw new \Exception(sprintf(
                        'No se puede restaurar la venta #%d: quedan %s de %s y la venta lleva %s. Registra una compra o un ajuste primero.',
                        $venta->id,
                        Cantidad::formato($producto->stock),
                        $producto->nombre,
                        Cantidad::formato($detalle->cantidad)
                    ));
                }

                $producto->decrement('stock', $detalle->cantidad);
            }

            if ($venta->tipo_pago === 'credito') {
                $this->verificarLimiteCredito($venta->cliente_id, (float) $venta->total);
            }

            $venta->update([
                'anulada' => false,
                'anulada_at' => null,
                'motivo_anulacion' => null,
                'anulada_por' => null,
            ]);

            DB::commit();

            return redirect()->route('ventas.index')->with('success', 'Venta #' . $venta->id . ' restaurada. Se volvió a descontar la mercancía del inventario.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('ventas.papelera')->withErrors(['error' => $e->getMessage()]);
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
            $fila(['N° Transaccion', 'Fecha', 'Atendido Por', 'Tipo de Pago', 'Cliente', 'Productos', 'Editada', 'Total $', 'Total Bs.', 'Tasa BCV', 'Cobrado en']);

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
                    $v->editada ? 'Sí' : 'No',
                    number_format($v->total, 2, '.', ''),
                    $v->total_ves !== null ? number_format($v->total_ves, 2, '.', '') : '',
                    $v->tasa_cambio !== null ? number_format($v->tasa_cambio, 4, '.', '') : '',
                    $v->tipo_pago === 'credito' ? 'Crédito' : ($v->moneda_pago === 'VES' ? 'Bolívares' : 'Dólares'),
                ]);
            }

        });
    }

    // Ventas del listado y del CSV: las de la papelera no salen aquí.
    private function ventasFiltradas(Request $request)
    {
        return Venta::with(['usuario', 'cliente', 'detalles.producto'])
            ->where('anulada', false)
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
            'cliente_id' => ['nullable', Rule::exists('clientes', 'id')->where('activo', true)],
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

        // El cliente es opcional en contado (queda en su ficha) y obligatorio en fiado (validarVenta)
        return [
            'cliente_id' => $request->cliente_id ?: null,
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
    private function registrarLineas(Venta $venta, array $items, array $productosPermitidos = []): float
    {
        $total = 0.0;

        foreach ($items as $item) {
            $producto = Producto::lockForUpdate()->findOrFail($item['id']);

            if (!$producto->activo && !in_array($producto->id, $productosPermitidos)) {
                throw new \Exception("El producto {$producto->nombre} está desactivado y no se puede vender.");
            }

            if ($error = $producto->errorCantidad($item['cantidad'])) {
                throw new \Exception($error);
            }

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
                'Esta venta supera el crédito disponible de %s: solo se le pueden fiar $%s más (debe $%s de un límite de $%s) y la venta es de $%s. Cobra una parte de contado o registra un abono primero.',
                $cliente->nombre,
                number_format($cliente->creditoDisponible($saldo), 2),
                number_format($saldo, 2),
                number_format($cliente->limite_credito, 2),
                number_format($total, 2)
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
