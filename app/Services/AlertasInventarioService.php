<?php

namespace App\Services;

use App\Models\DetalleVenta;
use App\Models\Producto;
use Illuminate\Support\Collection;

/**
 * Alertas de inventario (antes en su propia pantalla, ahora como filtros de Productos).
 * Los productos desactivados no generan alertas; solo salen en el filtro "Inactivos".
 */
class AlertasInventarioService
{
    // Filtro (en la URL: /productos?filtro=...) => etiqueta del botón
    public const FILTROS = [
        'todos' => 'Todos',
        'alertas' => 'Con alerta',
        'agotados' => 'Agotados',
        'stock_bajo' => 'Stock bajo',
        'vencimiento' => 'Vencidos / por vencer',
        'comprar' => 'Conviene comprar',
        'inactivos' => 'Inactivos',
    ];

    // Qué explica cada filtro debajo de los botones
    public const AYUDAS = [
        'todos' => 'Todos los productos registrados.',
        'alertas' => 'Todo lo que necesita atención: agotados, stock bajo, vencidos o por vencer y lo que conviene comprar.',
        'agotados' => 'Productos activos sin existencia. Toca "Comprar" para registrar lo que llegue.',
        'stock_bajo' => 'Les queda poco: están en su existencia mínima o por debajo.',
        'vencimiento' => 'Vencidos o que vencen en los próximos ' . self::DIAS_AVISO_VENCIMIENTO . ' días. Véndelos pronto o regístralos como pérdida.',
        'comprar' => 'Agotados, con stock bajo o que, según lo vendido en las últimas 4 semanas, se acabarían en la próxima semana.',
        'inactivos' => 'Productos desactivados: no se venden ni se compran, pero conservan su historial.',
    ];

    public const DIAS_AVISO_VENCIMIENTO = 5;

    public static function filtroValido(?string $filtro): string
    {
        return array_key_exists((string) $filtro, self::FILTROS) ? $filtro : 'todos';
    }

    public static function estaAgotado(Producto $p): bool
    {
        return $p->activo && $p->stock <= 0;
    }

    public static function tieneStockBajo(Producto $p): bool
    {
        return $p->activo && $p->stock > 0 && $p->stock <= $p->stock_minimo;
    }

    public static function alertaVencimiento(Producto $p): bool
    {
        $dias = $p->diasParaVencer();

        return $p->activo && $dias !== null && $dias <= self::DIAS_AVISO_VENCIMIENTO;
    }

    /**
     * Reparte los productos en cada filtro. Devuelve [filtro => Collection de productos].
     * "Conviene comprar" usa el promedio semanal vendido en las últimas 4 semanas (sin anuladas).
     */
    public function clasificar(Collection $productos): array
    {
        $promedioSemanal = DetalleVenta::join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
            ->where('ventas.fecha', '>=', now()->subWeeks(4)->startOfDay())
            ->where('ventas.anulada', false)
            ->groupBy('detalle_ventas.producto_id')
            ->selectRaw('detalle_ventas.producto_id, SUM(detalle_ventas.cantidad) as vendido')
            ->pluck('vendido', 'producto_id')
            ->map(fn ($vendido) => (float) $vendido / 4);

        $comprar = fn (Producto $p) => $p->activo && (
            self::estaAgotado($p)
            || self::tieneStockBajo($p)
            || ($promedioSemanal->get($p->id, 0) > 0 && $promedioSemanal->get($p->id) > $p->stock)
        );

        $grupos = [
            'todos' => $productos,
            'agotados' => $productos->filter(fn ($p) => self::estaAgotado($p))->values(),
            'stock_bajo' => $productos->filter(fn ($p) => self::tieneStockBajo($p))->values(),
            'vencimiento' => $productos->filter(fn ($p) => self::alertaVencimiento($p))
                ->sortBy(fn ($p) => $p->diasParaVencer())->values(),
            'comprar' => $productos->filter($comprar)->values(),
            'inactivos' => $productos->reject(fn ($p) => $p->activo)->values(),
        ];

        $idsConAlerta = collect(['agotados', 'stock_bajo', 'vencimiento', 'comprar'])
            ->flatMap(fn ($g) => $grupos[$g]->pluck('id'))
            ->unique();
        $grupos['alertas'] = $productos->filter(fn ($p) => $idsConAlerta->contains($p->id))->values();

        return $grupos;
    }
}
