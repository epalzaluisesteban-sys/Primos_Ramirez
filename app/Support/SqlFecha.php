<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Expresiones SQL de fecha que funcionan igual en MySQL (XAMPP) y PostgreSQL (Supabase).
 * Devuelven texto listo para DB::raw(), p. ej. "2026-10", "2026-10-31", "2026" o "202644" (año+semana ISO).
 */
class SqlFecha
{
    private static function pg(): bool
    {
        return DB::connection()->getDriverName() === 'pgsql';
    }

    public static function mes(string $col = 'fecha'): string
    {
        return self::pg() ? "to_char($col, 'YYYY-MM')" : "DATE_FORMAT($col, '%Y-%m')";
    }

    public static function dia(string $col = 'fecha'): string
    {
        return self::pg() ? "to_char($col, 'YYYY-MM-DD')" : "DATE_FORMAT($col, '%Y-%m-%d')";
    }

    public static function anio(string $col = 'fecha'): string
    {
        return self::pg() ? "to_char($col, 'YYYY')" : "DATE_FORMAT($col, '%Y')";
    }

    // Año + semana ISO (lunes a domingo), p. ej. 202644
    public static function semana(string $col = 'fecha'): string
    {
        return self::pg() ? "to_char($col, 'IYYYIW')" : "YEARWEEK($col, 1)";
    }
}
