<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\DetalleVenta;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use App\Support\SqlFecha;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteController extends Controller
{
    /**
     * Reporte de Ganancias: utilidad, márgen y comparativos.
     *
     * Metodología de costo: como el sistema no asocia un costo directo a
     * cada venta, se calcula el COSTO PROMEDIO PONDERADO por producto a
     * partir del historial de `compras` (SUM(cantidad*precio_compra) / SUM(cantidad)).
     * Los productos sin ninguna compra registrada no tienen costo conocido
     * y se marcan como tal en vez de asumir un valor arbitrario.
     */
    public function ganancias(Request $request)
    {
        $datos = $this->datosGanancias($request);

        // Comparativo mensual de ventas (últimos 6 meses)
        $ventasPorMes = Venta::select(
                DB::raw(SqlFecha::mes() . ' as mes'),
                DB::raw('SUM(total) as total')
            )
            ->where('fecha', '>=', now()->subMonths(5)->startOfMonth())
            ->where('anulada', false)
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        // % de variación del mes actual vs el anterior
        $variacionMensual = null;
        if ($ventasPorMes->count() >= 2) {
            $ultimo = $ventasPorMes->last()->total;
            $anterior = $ventasPorMes->slice(-2, 1)->first()->total;
            if ($anterior > 0) {
                $variacionMensual = (($ultimo - $anterior) / $anterior) * 100;
            }
        }

        return view('reportes.ganancias', array_merge($datos, [
            'ventasPorMes' => $ventasPorMes,
            'variacionMensual' => $variacionMensual,
        ]));
    }

    public function exportarExcel(Request $request): StreamedResponse
    {
        $datos = $this->datosGanancias($request);
        return $this->descargarCsv('ganancias_' . now()->format('Y-m-d_His') . '.csv', function (callable $fila) use ($datos) {
            $fila(['Producto', 'Unidades Vendidas', 'Ingreso', 'Costo', 'Utilidad', 'Margen %']);
            foreach ($datos['porProducto'] as $nombre => $p) {
                $fila([
                    $nombre,
                    $p['cantidad'],
                    number_format($p['ingreso'], 2, '.', ''),
                    $p['costo_conocido'] ? number_format($p['costo'], 2, '.', '') : 'N/D',
                    number_format($p['utilidad'], 2, '.', ''),
                    $p['costo_conocido'] ? number_format($p['margen'], 1, '.', '') : 'N/D',
                ]);
            }

            $fila([]);
            $fila(['Categoría', 'Unidades Vendidas', 'Ingreso', 'Costo', 'Utilidad', 'Margen %']);
            foreach ($datos['porCategoria'] as $nombre => $c) {
                $fila([
                    $nombre,
                    $c['cantidad'],
                    number_format($c['ingreso'], 2, '.', ''),
                    $c['costo_conocido'] ? number_format($c['costo'], 2, '.', '') : 'N/D',
                    number_format($c['utilidad'], 2, '.', ''),
                    $c['costo_conocido'] ? number_format($c['margen'], 1, '.', '') : 'N/D',
                ]);
            }

            $fila([]);
            $fila(['TOTAL', '', number_format($datos['ingresoTotal'], 2, '.', ''), number_format($datos['costoTotal'], 2, '.', ''), number_format($datos['utilidadTotal'], 2, '.', ''), number_format($datos['margenTotal'], 1, '.', '')]);

        });
    }

    public function exportarPDF(Request $request)
    {
        $datos = $this->datosGanancias($request);

        $pdf = Pdf::loadView('reportes.ganancias-pdf', $datos);
        return $pdf->download('Reporte_Ganancias_' . now()->format('Y-m-d') . '.pdf');
    }

    // Construye la utilidad/margen por producto y por categoría, respetando el
    // filtro opcional de fechas. Reutilizado por la vista y ambas exportaciones.
    private function datosGanancias(Request $request): array
    {
        $desde = $request->input('desde');
        $hasta = $request->input('hasta');

        // 1. Costo promedio ponderado por producto (basado en compras registradas)
        $costosPromedio = Compra::costosPromedio();

        // 2. Detalle de ventas (aplicando filtro de fechas si se envió)
        $detalles = DetalleVenta::with(['producto.categoria', 'venta'])
            ->whereHas('venta', function ($q) use ($desde, $hasta) {
                $q->where('anulada', false);
                if ($desde) $q->whereDate('fecha', '>=', $desde);
                if ($hasta) $q->whereDate('fecha', '<=', $hasta);
            })
            ->get();

        $ingresoTotal = 0;
        $costoTotal = 0;
        $hayCostoDesconocido = false;
        $porProducto = [];
        $porCategoria = [];

        foreach ($detalles as $d) {
            $nombreProducto = $d->producto->nombre ?? 'Producto eliminado';
            $nombreCategoria = $d->producto->categoria->nombre ?? 'Sin categoría';
            $costoUnitario = $costosPromedio[$d->producto_id] ?? null;

            $ingreso = (float) $d->subtotal;
            $costoConocido = $costoUnitario !== null;
            $costo = $costoConocido ? $costoUnitario * $d->cantidad : 0;

            if (!$costoConocido) {
                $hayCostoDesconocido = true;
            }

            $ingresoTotal += $ingreso;
            $costoTotal += $costo;

            if (!isset($porProducto[$nombreProducto])) {
                $porProducto[$nombreProducto] = [
                    'cantidad' => 0,
                    'ingreso' => 0,
                    'costo' => 0,
                    'costo_conocido' => $costoConocido,
                ];
            }
            $porProducto[$nombreProducto]['cantidad'] += $d->cantidad;
            $porProducto[$nombreProducto]['ingreso'] += $ingreso;
            $porProducto[$nombreProducto]['costo'] += $costo;
            $porProducto[$nombreProducto]['costo_conocido'] = $porProducto[$nombreProducto]['costo_conocido'] && $costoConocido;

            if (!isset($porCategoria[$nombreCategoria])) {
                $porCategoria[$nombreCategoria] = ['ingreso' => 0, 'cantidad' => 0, 'costo' => 0, 'costo_conocido' => true];
            }
            $porCategoria[$nombreCategoria]['ingreso'] += $ingreso;
            $porCategoria[$nombreCategoria]['cantidad'] += $d->cantidad;
            $porCategoria[$nombreCategoria]['costo'] += $costo;
            $porCategoria[$nombreCategoria]['costo_conocido'] = $porCategoria[$nombreCategoria]['costo_conocido'] && $costoConocido;
        }

        foreach ($porProducto as $nombre => &$p) {
            $p['utilidad'] = $p['ingreso'] - $p['costo'];
            $p['margen'] = $p['ingreso'] > 0 ? ($p['utilidad'] / $p['ingreso']) * 100 : 0;
        }
        unset($p);

        foreach ($porCategoria as $nombre => &$c) {
            $c['utilidad'] = $c['ingreso'] - $c['costo'];
            $c['margen'] = $c['ingreso'] > 0 ? ($c['utilidad'] / $c['ingreso']) * 100 : 0;
        }
        unset($c);

        uasort($porCategoria, fn ($a, $b) => $b['utilidad'] <=> $a['utilidad']);
        uasort($porProducto, fn ($a, $b) => $b['utilidad'] <=> $a['utilidad']);

        $utilidadTotal = $ingresoTotal - $costoTotal;
        $margenTotal = $ingresoTotal > 0 ? ($utilidadTotal / $ingresoTotal) * 100 : 0;

        return compact(
            'ingresoTotal',
            'costoTotal',
            'utilidadTotal',
            'margenTotal',
            'hayCostoDesconocido',
            'porProducto',
            'porCategoria',
            'desde',
            'hasta'
        );
    }
}
