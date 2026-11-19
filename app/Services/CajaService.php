<?php

namespace App\Services;

use App\Models\Abono;
use App\Models\CierreCaja;
use App\Models\DetalleVenta;
use App\Models\Gasto;
use App\Models\PagoProveedor;
use App\Models\TasaCambio;
use App\Models\Venta;
use Illuminate\Support\Carbon;

/**
 * Cuadre del efectivo de un día y reporte de todo lo que pasó en él. Lo usan la pantalla de caja,
 * el cierre, el registro de gastos, el PDF del día y el ticket, para que todos digan lo mismo.
 */
class CajaService
{
    /**
     * Efectivo que entra y sale del cajón en el día, por moneda: ventas de contado (sin anuladas)
     * y abonos entran; gastos salen. Lo cobrado en $ y en Bs. se cuenta por separado, como el
     * efectivo físico. Las ventas a crédito no mueven efectivo hasta que el cliente abona.
     */
    public function resumen(\DateTimeInterface|string $fecha): array
    {
        $dia = Carbon::parse($fecha)->toDateString();

        $contado = Venta::whereDate('fecha', $dia)->where('anulada', false)->where('tipo_pago', 'contado');
        $contadoUsd = (float) (clone $contado)->where('moneda_pago', 'USD')->sum('total');
        $contadoVes = (float) (clone $contado)->where('moneda_pago', 'VES')->sum('total_ves');
        $credito = (float) Venta::whereDate('fecha', $dia)->where('anulada', false)->where('tipo_pago', 'credito')->sum('total');

        $abonos = Abono::whereDate('fecha', $dia);
        $abonosUsd = (float) (clone $abonos)->where('moneda', 'USD')->sum('monto');
        $abonosVes = (float) (clone $abonos)->where('moneda', 'VES')->sum('monto_moneda');

        $gastos = Gasto::whereDate('fecha', $dia);
        $gastosUsd = (float) (clone $gastos)->where('moneda', 'USD')->sum('monto_moneda');
        $gastosVes = (float) (clone $gastos)->where('moneda', 'VES')->sum('monto_moneda');

        // Solo los pagos a proveedores hechos con el efectivo del cajón
        $pagos = PagoProveedor::whereNotNull('cierre_caja_id')->whereDate('fecha', $dia);
        $pagosUsd = (float) (clone $pagos)->where('moneda', 'USD')->sum('monto_moneda');
        $pagosVes = (float) (clone $pagos)->where('moneda', 'VES')->sum('monto_moneda');

        return [
            'contado_usd' => $contadoUsd,
            'contado_ves' => $contadoVes,
            'abonos_usd' => $abonosUsd,
            'abonos_ves' => $abonosVes,
            'credito' => $credito,
            'ingresos_usd' => round($contadoUsd + $abonosUsd, 2),
            'ingresos_ves' => round($contadoVes + $abonosVes, 2),
            'gastos_usd' => round($gastosUsd, 2),
            'gastos_ves' => round($gastosVes, 2),
            'pagos_usd' => round($pagosUsd, 2),
            'pagos_ves' => round($pagosVes, 2),
            'salidas_usd' => round($gastosUsd + $pagosUsd, 2),
            'salidas_ves' => round($gastosVes + $pagosVes, 2),
        ];
    }

    /** Efectivo que debería haber en el cajón: fondo inicial + ingresos − gastos − pagos a proveedores, por moneda. */
    public function esperado(CierreCaja $caja, array $resumen): array
    {
        return [
            'usd' => round($caja->monto_apertura + $resumen['ingresos_usd'] - $resumen['salidas_usd'], 2),
            'ves' => round($caja->monto_apertura_ves + $resumen['ingresos_ves'] - $resumen['salidas_ves'], 2),
        ];
    }

    /** Todo lo que se hizo en el día de la caja: para el PDF de cierre y el ticket. */
    public function reporte(CierreCaja $caja): array
    {
        $caja->loadMissing(['usuario', 'cerradaPor']);
        $dia = Carbon::parse($caja->fecha)->toDateString();
        $resumen = $this->resumen($dia);

        $ventas = Venta::with(['cliente', 'usuario'])->whereDate('fecha', $dia)->orderBy('fecha')->get();
        $validas = $ventas->where('anulada', false)->values();

        $productos = DetalleVenta::with('producto')
            ->whereHas('venta', fn ($q) => $q->whereDate('fecha', $dia)->where('anulada', false))
            ->get()
            ->groupBy('producto_id')
            ->map(fn ($lineas) => [
                'producto' => $lineas->first()->producto,
                'cantidad' => (float) $lineas->sum('cantidad'),
                'total' => round((float) $lineas->sum('subtotal'), 2),
            ])
            ->sortByDesc('total')
            ->values();

        $tasa = TasaCambio::whereDate('fecha', '<=', $dia)->orderByDesc('fecha')->orderByDesc('id')->value('tasa');

        return [
            'caja' => $caja,
            'resumen' => $resumen,
            'esperado' => $this->esperado($caja, $resumen),
            'ventas' => $validas,
            'fiados' => $validas->where('tipo_pago', 'credito')->values(),
            'anuladas' => $ventas->where('anulada', true)->values(),
            'productos' => $productos,
            'abonos' => Abono::with(['cliente', 'usuario'])->whereDate('fecha', $dia)->orderBy('fecha')->get(),
            'gastos' => Gasto::with('usuario')->whereDate('fecha', $dia)->orderBy('fecha')->get(),
            'pagosProveedor' => PagoProveedor::with(['proveedor', 'usuario'])->whereDate('fecha', $dia)->orderBy('fecha')->get(),
            'tasa' => $tasa ? (float) $tasa : null,
            'totalVendido' => round((float) $validas->sum('total'), 2),
        ];
    }
}
