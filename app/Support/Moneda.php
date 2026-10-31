<?php

namespace App\Support;

use App\Services\TasaCambioService;

/**
 * Conversión y formato de moneda. La moneda base del sistema es el USD; los bolívares
 * (VES) se calculan con la tasa oficial del BCV (o la tasa guardada en cada operación).
 */
class Moneda
{
    public static function tasa(): float
    {
        return TasaCambioService::valor();
    }

    public static function aVes(float|int|string|null $usd, ?float $tasa = null): float
    {
        return round((float) $usd * ($tasa ?: self::tasa()), 2);
    }

    public static function aUsd(float|int|string|null $ves, ?float $tasa = null): float
    {
        $tasa = $tasa ?: self::tasa();

        return $tasa > 0 ? round((float) $ves / $tasa, 2) : 0.0;
    }

    /** "$1,234.50" */
    public static function usd(float|int|string|null $monto, int $decimales = 2): string
    {
        return '$' . number_format((float) $monto, $decimales, '.', ',');
    }

    /** "Bs. 1.234,50" (formato venezolano) */
    public static function ves(float|int|string|null $monto, int $decimales = 2): string
    {
        return 'Bs. ' . number_format((float) $monto, $decimales, ',', '.');
    }

    /** Equivalente en bolívares de un monto en USD: "Bs. 1.234,50". */
    public static function usdEnVes(float|int|string|null $usd, ?float $tasa = null): string
    {
        return self::ves(self::aVes($usd, $tasa));
    }
}
