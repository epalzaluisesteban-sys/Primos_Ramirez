<?php

namespace App\Services;

use App\Models\Abono;
use App\Models\AjusteInventario;
use App\Models\CierreCaja;
use App\Models\Compra;
use App\Models\Merma;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Línea de tiempo unificada (ventas, compras, mermas, abonos y cierres de caja).
 * La comparten el feed del Dashboard y la bitácora del Historial.
 */
class ActividadService
{
    /**
     * @param  Carbon|null  $inicio  Límite inferior de fecha (null = sin límite)
     * @param  Carbon|null  $fin     Límite superior de fecha (null = sin límite)
     * @param  int|null     $limite  Máximo de eventos por tipo, los más recientes (null = todos)
     */
    public function eventos(?Carbon $inicio = null, ?Carbon $fin = null, ?int $limite = null): Collection
    {
        $consulta = function ($query) use ($inicio, $fin, $limite) {
            if ($inicio && $fin) {
                $query->whereBetween('fecha', [$inicio, $fin]);
            }
            if ($limite) {
                $query->latest('fecha')->take($limite);
            }
            return $query->get();
        };

        $eventos = collect();

        foreach ($consulta(Venta::with('usuario')) as $v) {
            $eventos->push([
                'tipo' => 'Venta',
                'icono' => 'cart',
                'color' => 'emerald',
                'texto' => $v->anulada ? "Venta #{$v->id} enviada a la papelera" : "Venta #{$v->id} registrada",
                'monto' => (float) $v->total,
                'usuario' => $v->usuario->name ?? 'Sistema',
                'fecha' => $v->fecha,
            ]);
        }

        foreach ($consulta(Compra::with(['usuario', 'producto'])) as $c) {
            $eventos->push([
                'tipo' => 'Compra',
                'icono' => 'inbox-down',
                'color' => 'blue',
                'texto' => 'Compra: ' . ($c->producto->nombre ?? 'Producto eliminado'),
                'monto' => (float) $c->total,
                'usuario' => $c->usuario->name ?? 'Sistema',
                'fecha' => Carbon::parse($c->fecha),
            ]);
        }

        foreach ($consulta(Merma::with(['usuario', 'producto'])) as $m) {
            $eventos->push([
                'tipo' => 'Pérdida',
                'icono' => 'package-x',
                'color' => 'rose',
                'texto' => 'Pérdida: ' . $m->cantidad . ' ' . ($m->producto->unidadLabel() ?? '') . ' de ' . ($m->producto->nombre ?? 'Producto eliminado'),
                'monto' => null,
                'usuario' => $m->usuario->name ?? 'Sistema',
                'fecha' => $m->fecha,
            ]);
        }

        foreach ($consulta(Abono::with(['usuario', 'cliente'])) as $a) {
            $eventos->push([
                'tipo' => 'Abono',
                'icono' => 'banknote',
                'color' => 'amber',
                'texto' => 'Abono de ' . ($a->cliente->nombre ?? 'Cliente eliminado'),
                'monto' => (float) $a->monto,
                'usuario' => $a->usuario->name ?? 'Sistema',
                'fecha' => $a->fecha,
            ]);
        }

        foreach ($consulta(AjusteInventario::with(['usuario', 'producto'])) as $a) {
            $eventos->push([
                'tipo' => 'Ajuste',
                'icono' => 'box',
                'color' => 'stone',
                'texto' => 'Ajuste de stock: ' . ($a->cantidad > 0 ? '+' : '') . $a->cantidad . ' ' . ($a->producto?->unidadLabel() ?? '') . ' de ' . ($a->producto->nombre ?? 'Producto eliminado') . ($a->motivo ? " ({$a->motivo})" : ''),
                'monto' => null,
                'usuario' => $a->usuario->name ?? 'Sistema',
                'fecha' => $a->fecha,
            ]);
        }

        // La caja se ubica en el tiempo por su última actualización (apertura o cierre).
        $cierres = CierreCaja::with('usuario');
        if ($inicio && $fin) {
            $cierres->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()]);
        }
        if ($limite) {
            $cierres->latest('updated_at')->take($limite);
        }
        foreach ($cierres->get() as $c) {
            $cerrada = $c->estado === 'CERRADA';
            $eventos->push([
                'tipo' => 'Caja',
                'icono' => $cerrada ? 'lock' : 'check-circle',
                'color' => $cerrada ? 'stone' : 'emerald',
                'texto' => $cerrada ? 'Caja cerrada' : 'Caja abierta',
                'monto' => null,
                'usuario' => $c->usuario->name ?? 'Sistema',
                'fecha' => Carbon::parse($c->updated_at),
            ]);
        }

        return $eventos->sortByDesc('fecha')->values();
    }
}
