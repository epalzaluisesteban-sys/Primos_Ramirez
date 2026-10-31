<?php

namespace App\Http\Controllers;

use App\Models\CierreCaja;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Services\ActividadService;
use App\Support\Moneda;
use App\Support\SqlFecha;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $hoy = now()->startOfDay();
        $inicioMes = now()->startOfMonth();
        $finMes = now()->endOfMonth();
        $inicioMesAnterior = now()->subMonthNoOverflow()->startOfMonth();
        $finMesAnterior = now()->subMonthNoOverflow()->endOfMonth();

        // ==========================================
        // 1. Ventas del día
        // ==========================================
        $ventasHoyMonto = Venta::whereDate('fecha', $hoy)->where('anulada', false)->sum('total');
        $ventasHoyCant = Venta::whereDate('fecha', $hoy)->where('anulada', false)->count();
        $ventasHoyVes = $this->totalEnBolivares(Venta::whereDate('fecha', $hoy)->where('anulada', false)->get());

        // ==========================================
        // 2. Ventas del mes (+ variación vs. mes anterior)
        // ==========================================
        $ventasMesActual = (float) Venta::whereBetween('fecha', [$inicioMes, $finMes])->where('anulada', false)->sum('total');
        $ventasMesAnterior = (float) Venta::whereBetween('fecha', [$inicioMesAnterior, $finMesAnterior])->where('anulada', false)->sum('total');
        $ventasMesVes = $this->totalEnBolivares(Venta::whereBetween('fecha', [$inicioMes, $finMes])->where('anulada', false)->get());
        $variacionVentasMes = $ventasMesAnterior > 0 ? (($ventasMesActual - $ventasMesAnterior) / $ventasMesAnterior) * 100 : null;

        // ==========================================
        // 3. Compras del mes (+ variación vs. mes anterior)
        // ==========================================
        $comprasMesActual = (float) Compra::whereBetween('fecha', [$inicioMes, $finMes])->sum('total');
        $comprasMesAnterior = (float) Compra::whereBetween('fecha', [$inicioMesAnterior, $finMesAnterior])->sum('total');
        $variacionComprasMes = $comprasMesAnterior > 0 ? (($comprasMesActual - $comprasMesAnterior) / $comprasMesAnterior) * 100 : null;

        // ==========================================
        // 4. Ganancia neta del mes (costo promedio ponderado, misma metodología que ReporteController)
        // ==========================================
        $costosPromedio = Compra::costosPromedio();

        $detallesMes = DetalleVenta::whereHas('venta', function ($q) use ($inicioMes, $finMes) {
                $q->whereBetween('fecha', [$inicioMes, $finMes])->where('anulada', false);
            })
            ->get();

        $ingresoMes = (float) $detallesMes->sum('subtotal');
        $costoMes = (float) $detallesMes->sum(fn ($d) => ($costosPromedio[$d->producto_id] ?? 0) * $d->cantidad);
        $gananciaNetaMes = $ingresoMes - $costoMes;
        $margenMes = $ingresoMes > 0 ? ($gananciaNetaMes / $ingresoMes) * 100 : 0;

        // ==========================================
        // 5. Productos agotándose (stock crítico)
        // ==========================================
        $productosStockBajo = Producto::whereColumn('stock', '<=', 'stock_minimo')
            ->with('categoria')
            ->orderBy('stock', 'asc')
            ->get();

        // ==========================================
        // 6. Productos vencidos vs. próximos a vencer (≤ 5 días)
        // ==========================================
        $productosVencidos = Producto::whereNotNull('fecha_vencimiento')
            ->whereDate('fecha_vencimiento', '<', $hoy)
            ->with('categoria')
            ->orderBy('fecha_vencimiento', 'asc')
            ->get();

        $productosPorVencer = Producto::whereNotNull('fecha_vencimiento')
            ->whereDate('fecha_vencimiento', '>=', $hoy)
            ->whereDate('fecha_vencimiento', '<=', now()->addDays(5))
            ->with('categoria')
            ->orderBy('fecha_vencimiento', 'asc')
            ->get();

        // ==========================================
        // 7. Productos más vendidos del mes
        // ==========================================
        $topProductosMes = DetalleVenta::select('producto_id', DB::raw('SUM(cantidad) as total_vendido'), DB::raw('SUM(subtotal) as total_ingreso'))
            ->whereHas('venta', function ($q) use ($inicioMes, $finMes) {
                $q->whereBetween('fecha', [$inicioMes, $finMes])->where('anulada', false);
            })
            ->with('producto')
            ->groupBy('producto_id')
            ->orderByDesc('total_vendido')
            ->take(6)
            ->get();

        $nombresTopProductos = $topProductosMes->map(fn ($item) => $item->producto->nombre ?? 'Eliminado');
        $cantidadesTopProductos = $topProductosMes->pluck('total_vendido');

        // ==========================================
        // 8. Clientes frecuentes (por gasto total)
        // ==========================================
        $clientesFrecuentes = Cliente::withCount(['ventas' => fn ($q) => $q->where('anulada', false)])
            ->withSum(['ventas' => fn ($q) => $q->where('anulada', false)], 'total')
            ->get()
            ->filter(fn ($c) => $c->ventas_count > 0)
            ->sortByDesc('ventas_sum_total')
            ->take(5)
            ->values();

        // ==========================================
        // 9. Últimas ventas y últimas compras
        // ==========================================
        $ultimasVentas = Venta::with(['usuario', 'cliente'])->latest()->take(6)->get();
        $ultimasCompras = Compra::with(['producto', 'usuario', 'proveedorRef'])->latest()->take(6)->get();

        // ==========================================
        // 10. Actividad reciente (feed unificado)
        // ==========================================
        $actividadReciente = app(ActividadService::class)->eventos(limite: 5)->take(12);

        // ==========================================
        // 11. Caja del día
        // ==========================================
        $cajaHoy = CierreCaja::whereDate('fecha', $hoy)->first();

        // ==========================================
        // 12. Estado del inventario
        // ==========================================
        $totalProductosInv = Producto::count();
        $unidadesEnStock = (float) Producto::sum('stock');
        $valorInventario = (float) Producto::sum(DB::raw('stock * precio'));

        $idsCriticos = $productosStockBajo->pluck('id');
        $idsVencidos = $productosVencidos->pluck('id');
        $idsConProblema = $idsCriticos->merge($idsVencidos)->unique();
        $productosNormales = max(0, $totalProductosInv - $idsConProblema->count());

        // ==========================================
        // 13. Datos para gráficos
        // ==========================================

        // a) Línea: tendencia de ventas de los últimos 14 días
        $ventasUltimos14Dias = Venta::select(
                DB::raw('DATE(fecha) as fecha_dia'),
                DB::raw('SUM(total) as total_dia')
            )
            ->where('fecha', '>=', now()->subDays(13)->startOfDay())
            ->where('anulada', false)
            ->groupBy('fecha_dia')
            ->orderBy('fecha_dia', 'asc')
            ->get();

        $fechasGrafico = $ventasUltimos14Dias->pluck('fecha_dia');
        $totalesGrafico = $ventasUltimos14Dias->pluck('total_dia');

        // b) Pastel: ventas del mes por tipo de pago
        $ventasPorTipoPago = Venta::select('tipo_pago', DB::raw('SUM(total) as total'))
            ->whereBetween('fecha', [$inicioMes, $finMes])
            ->where('anulada', false)
            ->groupBy('tipo_pago')
            ->get();

        $etiquetasTipoPago = $ventasPorTipoPago->map(fn ($v) => $v->tipo_pago === 'credito' ? 'Fiado / Crédito' : 'Contado');
        $totalesTipoPago = $ventasPorTipoPago->pluck('total');

        // c) Barras comparativas: Ventas vs. Compras de los últimos 6 meses
        $ventasPorMes6 = Venta::select(DB::raw(SqlFecha::mes() . ' as mes'), DB::raw('SUM(total) as total'))
            ->where('fecha', '>=', now()->subMonths(5)->startOfMonth())
            ->where('anulada', false)
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        $comprasPorMes6 = Compra::select(DB::raw(SqlFecha::mes() . ' as mes'), DB::raw('SUM(total) as total'))
            ->where('fecha', '>=', now()->subMonths(5)->startOfMonth())
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        $mesesComparativo = $ventasPorMes6->pluck('mes')->merge($comprasPorMes6->pluck('mes'))->unique()->sort()->values();
        $ventasPorMesMap = $ventasPorMes6->pluck('total', 'mes');
        $comprasPorMesMap = $comprasPorMes6->pluck('total', 'mes');
        $serieVentasComparativo = $mesesComparativo->map(fn ($m) => (float) ($ventasPorMesMap[$m] ?? 0));
        $serieComprasComparativo = $mesesComparativo->map(fn ($m) => (float) ($comprasPorMesMap[$m] ?? 0));

        return view('dashboard', compact(
            'ventasHoyMonto',
            'ventasHoyCant',
            'ventasHoyVes',
            'ventasMesVes',
            'ventasMesActual',
            'variacionVentasMes',
            'comprasMesActual',
            'variacionComprasMes',
            'gananciaNetaMes',
            'margenMes',
            'productosStockBajo',
            'productosVencidos',
            'productosPorVencer',
            'nombresTopProductos',
            'cantidadesTopProductos',
            'topProductosMes',
            'clientesFrecuentes',
            'ultimasVentas',
            'ultimasCompras',
            'actividadReciente',
            'cajaHoy',
            'totalProductosInv',
            'unidadesEnStock',
            'valorInventario',
            'productosNormales',
            'fechasGrafico',
            'totalesGrafico',
            'etiquetasTipoPago',
            'totalesTipoPago',
            'mesesComparativo',
            'serieVentasComparativo',
            'serieComprasComparativo'
        ));
    }

    // Suma en bolívares con la tasa de cada venta (si no tiene, la tasa vigente).
    private function totalEnBolivares($ventas): float
    {
        return round($ventas->sum(fn ($v) => $v->total_ves ?? Moneda::aVes($v->total)), 2);
    }
}
