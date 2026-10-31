<?php

namespace App\Http\Controllers;

use App\Models\DetalleVenta;
use App\Models\Producto;

class InventarioController extends Controller
{
    public function alertas()
    {
        $hoy = now()->startOfDay();

        $agotados = Producto::agotados()->with('categoria')->orderBy('nombre')->get();
        $criticos = Producto::stockCritico()->with('categoria')->orderBy('stock')->get();

        $vencidos = Producto::whereNotNull('fecha_vencimiento')
            ->whereDate('fecha_vencimiento', '<', $hoy)
            ->with('categoria')
            ->orderBy('fecha_vencimiento')
            ->get();

        $porVencer = Producto::whereNotNull('fecha_vencimiento')
            ->whereDate('fecha_vencimiento', '>=', $hoy)
            ->whereDate('fecha_vencimiento', '<=', now()->addDays(5))
            ->with('categoria')
            ->orderBy('fecha_vencimiento')
            ->get();

        // Sugerencias de compra: agotados/críticos, más productos cuya proyección de
        // demanda de la próxima semana (promedio móvil de 4 semanas) supera el stock actual.
        $idsUrgentes = $agotados->pluck('id')->merge($criticos->pluck('id'));

        $ventasRecientes = DetalleVenta::join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
            ->where('ventas.fecha', '>=', now()->subWeeks(4)->startOfDay())
            ->where('ventas.anulada', false)
            ->select('detalle_ventas.producto_id', 'detalle_ventas.cantidad')
            ->get()
            ->groupBy('producto_id');

        $sugerenciasCompra = Producto::with('categoria')->orderBy('nombre')->get()
            ->filter(function ($p) use ($ventasRecientes, $idsUrgentes) {
                if ($idsUrgentes->contains($p->id)) {
                    return true;
                }
                $promedioSemanal = $ventasRecientes->get($p->id, collect())->sum('cantidad') / 4;

                return $promedioSemanal > 0 && $promedioSemanal > $p->stock;
            })
            ->values();

        return view('inventario.alertas', compact(
            'agotados',
            'criticos',
            'vencidos',
            'porVencer',
            'sugerenciasCompra'
        ));
    }
}
