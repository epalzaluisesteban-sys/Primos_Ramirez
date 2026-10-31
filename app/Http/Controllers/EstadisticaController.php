<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Compra;
use App\Models\DetalleVenta;
use App\Models\Merma;
use App\Models\Producto;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Support\SqlFecha;
use Illuminate\Support\Facades\DB;

class EstadisticaController extends Controller
{
    // Días sin comprar a partir de los cuales un cliente se considera "que dejó de comprar"
    const DIAS_INACTIVIDAD_CLIENTE = 30;

    public function index(Request $request)
    {
        // ==========================================
        // 1. Ventas por período (día / semana / mes / año)
        // ==========================================
        $agrupacion = $request->input('agrupacion', 'mes');
        if (!in_array($agrupacion, ['dia', 'semana', 'mes', 'anio'])) {
            $agrupacion = 'mes';
        }

        switch ($agrupacion) {
            case 'dia':
                $expresionPeriodo = SqlFecha::dia();
                $desdeVentas = now()->subDays(29)->startOfDay();
                break;
            case 'semana':
                $expresionPeriodo = SqlFecha::semana();
                $desdeVentas = now()->subWeeks(11)->startOfWeek();
                break;
            case 'anio':
                $expresionPeriodo = SqlFecha::anio();
                $desdeVentas = now()->subYears(4)->startOfYear();
                break;
            case 'mes':
            default:
                $expresionPeriodo = SqlFecha::mes();
                $desdeVentas = now()->subMonths(11)->startOfMonth();
                break;
        }

        $ventasPorPeriodo = Venta::select(
                DB::raw("$expresionPeriodo as periodo"),
                DB::raw('SUM(total) as total'),
                DB::raw('COUNT(*) as cantidad')
            )
            ->where('fecha', '>=', $desdeVentas)
            ->where('anulada', false)
            ->groupBy('periodo')
            ->orderBy('periodo')
            ->get()
            ->map(function ($item) use ($agrupacion) {
                $item->etiqueta = $agrupacion === 'semana'
                    ? $this->etiquetaSemana((string) $item->periodo)
                    : (string) $item->periodo;
                return $item;
            });

        // ==========================================
        // 2. Comparativo de ventas por categoría (histórico completo)
        // ==========================================
        $porCategoria = DetalleVenta::join('productos', 'detalle_ventas.producto_id', '=', 'productos.id')
            ->join('categorias', 'productos.categoria_id', '=', 'categorias.id')
            ->join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
            ->where('ventas.anulada', false)
            ->select(
                'categorias.nombre as categoria',
                DB::raw('SUM(detalle_ventas.subtotal) as ingreso'),
                DB::raw('SUM(detalle_ventas.cantidad) as unidades')
            )
            ->groupBy('categorias.nombre')
            ->orderByDesc('ingreso')
            ->get();

        // ==========================================
        // 3. Top proveedores por monto total comprado
        // ==========================================
        $topProveedores = Compra::select(
                DB::raw("COALESCE(proveedor, 'Sin especificar') as nombre_proveedor"),
                DB::raw('SUM(total) as total_comprado'),
                DB::raw('COUNT(*) as cantidad_compras')
            )
            ->groupBy('nombre_proveedor')
            ->orderByDesc('total_comprado')
            ->take(6)
            ->get();

        // ==========================================
        // 4. Mermas: unidades y valor estimado perdido por mes (últimos 6 meses)
        // ==========================================
        $mermas = Merma::with('producto')
            ->where('fecha', '>=', now()->subMonths(5)->startOfMonth())
            ->get();

        $mermasPorMes = $mermas->groupBy(fn ($m) => $m->fecha->format('Y-m'))
            ->map(function ($grupo) {
                return [
                    'unidades' => $grupo->sum('cantidad'),
                    'valor' => $grupo->sum(fn ($m) => $m->valorEstimado()),
                ];
            })->sortKeys();

        $totalMermaValor = $mermas->sum(fn ($m) => $m->valorEstimado());
        $totalMermaUnidades = $mermas->sum('cantidad');

        // ==========================================
        // 5. Predicción simple de demanda (promedio móvil 4 semanas)
        // ==========================================
        $productos = Producto::orderBy('nombre')->get();
        $inicioVentana = now()->subWeeks(4)->startOfDay();

        $ventasRecientes = DetalleVenta::join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
            ->where('ventas.fecha', '>=', $inicioVentana)
            ->where('ventas.anulada', false)
            ->select('detalle_ventas.producto_id', 'detalle_ventas.cantidad', 'ventas.fecha')
            ->get()
            ->groupBy('producto_id');

        $prediccion = $productos->map(function ($producto) use ($ventasRecientes) {
            $ventas = $ventasRecientes->get($producto->id, collect());
            $totalVendido = $ventas->sum('cantidad');
            $promedioSemanal = round($totalVendido / 4, 2);

            return [
                'producto' => $producto,
                'vendido_4_semanas' => $totalVendido,
                'promedio_semanal' => $promedioSemanal,
                'proyeccion_proxima_semana' => $promedioSemanal,
                'cubre_con_stock' => $producto->stock >= $promedioSemanal,
            ];
        })
        ->filter(fn ($item) => $item['vendido_4_semanas'] > 0)
        ->sortByDesc('vendido_4_semanas')
        ->take(10)
        ->values();

        // ==========================================
        // 6. Productos más vendidos / menos vendidos (histórico)
        // ==========================================
        $ventasPorProducto = DetalleVenta::select('producto_id', DB::raw('SUM(cantidad) as total_vendido'), DB::raw('SUM(subtotal) as total_ingreso'))
            ->whereHas('venta', fn ($q) => $q->where('anulada', false))
            ->with('producto')
            ->groupBy('producto_id')
            ->get();

        $productosMasVendidos = $ventasPorProducto->sortByDesc('total_vendido')->take(10)->values();
        $productosMenosVendidos = $ventasPorProducto->sortBy('total_vendido')->take(10)->values();
        $productosNuncaVendidos = Producto::whereDoesntHave('detallesVenta')->count();

        // ==========================================
        // 7. Clientes que más compran
        // ==========================================
        $clientesFrecuentes = Cliente::withCount(['ventas' => fn ($q) => $q->where('anulada', false)])
            ->withSum(['ventas' => fn ($q) => $q->where('anulada', false)], 'total')
            ->get()
            ->filter(fn ($c) => $c->ventas_count > 0)
            ->map(function ($c) {
                $c->ticket_promedio = $c->ventas_count > 0 ? $c->ventas_sum_total / $c->ventas_count : 0;
                return $c;
            })
            ->sortByDesc('ventas_sum_total')
            ->take(15)
            ->values();

        // ==========================================
        // 8. Clientes que dejaron de comprar (activos, con historial, sin compras recientes)
        // ==========================================
        $clientesInactivos = Cliente::where('activo', true)
            ->withMax(['ventas' => fn ($q) => $q->where('anulada', false)], 'fecha')
            ->get()
            ->filter(fn ($c) => $c->ventas_max_fecha !== null && Carbon::parse($c->ventas_max_fecha)->lt(now()->subDays(self::DIAS_INACTIVIDAD_CLIENTE)))
            ->map(function ($c) {
                $c->dias_sin_comprar = Carbon::parse($c->ventas_max_fecha)->diffInDays(now());
                $c->saldo = $c->saldoPendiente();
                return $c;
            })
            ->sortByDesc('dias_sin_comprar')
            ->take(15)
            ->values();

        // ==========================================
        // 9. Comparación: mes actual vs. mes anterior
        // ==========================================
        $inicioMes = now()->startOfMonth();
        $finMes = now()->endOfMonth();
        $inicioMesAnterior = now()->subMonthNoOverflow()->startOfMonth();
        $finMesAnterior = now()->subMonthNoOverflow()->endOfMonth();

        $ventasMesActualMonto = (float) Venta::whereBetween('fecha', [$inicioMes, $finMes])->where('anulada', false)->sum('total');
        $ventasMesActualCant = Venta::whereBetween('fecha', [$inicioMes, $finMes])->where('anulada', false)->count();
        $ventasMesAnteriorMonto = (float) Venta::whereBetween('fecha', [$inicioMesAnterior, $finMesAnterior])->where('anulada', false)->sum('total');
        $ventasMesAnteriorCant = Venta::whereBetween('fecha', [$inicioMesAnterior, $finMesAnterior])->where('anulada', false)->count();

        $comprasMesActual = (float) Compra::whereBetween('fecha', [$inicioMes, $finMes])->sum('total');
        $comprasMesAnterior = (float) Compra::whereBetween('fecha', [$inicioMesAnterior, $finMesAnterior])->sum('total');

        $comparacionMensual = [
            'ventas_actual' => $ventasMesActualMonto,
            'ventas_anterior' => $ventasMesAnteriorMonto,
            'variacion_ventas' => $ventasMesAnteriorMonto > 0 ? (($ventasMesActualMonto - $ventasMesAnteriorMonto) / $ventasMesAnteriorMonto) * 100 : null,
            'tickets_actual' => $ventasMesActualCant,
            'tickets_anterior' => $ventasMesAnteriorCant,
            'ticket_prom_actual' => $ventasMesActualCant > 0 ? $ventasMesActualMonto / $ventasMesActualCant : 0,
            'ticket_prom_anterior' => $ventasMesAnteriorCant > 0 ? $ventasMesAnteriorMonto / $ventasMesAnteriorCant : 0,
            'compras_actual' => $comprasMesActual,
            'compras_anterior' => $comprasMesAnterior,
            'variacion_compras' => $comprasMesAnterior > 0 ? (($comprasMesActual - $comprasMesAnterior) / $comprasMesAnterior) * 100 : null,
        ];

        return view('estadisticas.index', compact(
            'agrupacion',
            'ventasPorPeriodo',
            'porCategoria',
            'topProveedores',
            'mermasPorMes',
            'totalMermaValor',
            'totalMermaUnidades',
            'prediccion',
            'productosMasVendidos',
            'productosMenosVendidos',
            'productosNuncaVendidos',
            'clientesFrecuentes',
            'clientesInactivos',
            'comparacionMensual'
        ));
    }

    // Convierte el resultado de SqlFecha::semana() (p. ej. "202630") a una etiqueta legible
    private function etiquetaSemana(string $yearweek): string
    {
        $anio = substr($yearweek, 0, 4);
        $semana = (int) substr($yearweek, 4, 2);

        return "Sem. {$semana} — {$anio}";
    }
}
