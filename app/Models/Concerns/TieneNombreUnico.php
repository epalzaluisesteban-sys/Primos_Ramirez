<?php

namespace App\Models\Concerns;

use App\Support\Unicidad;

/**
 * Modelos cuyo nombre no se puede repetir (sin importar mayúsculas, tildes ni espacios). La columna
 * `nombre_normalizado` lleva el índice único y se llena sola al guardar.
 */
trait TieneNombreUnico
{
    protected static function bootTieneNombreUnico(): void
    {
        static::saving(function ($modelo) {
            $modelo->nombre = preg_replace('/\s+/', ' ', trim((string) $modelo->nombre));
            $modelo->nombre_normalizado = $modelo->claveNombre();
        });
    }

    public function claveNombre(): string
    {
        return Unicidad::normalizar($this->nombre);
    }

    /** El registro que ya usa esa clave (activo o no), sin contar el que se está editando. */
    public static function conClave(string $clave, ?int $ignorarId = null, array $donde = []): ?static
    {
        return static::where('nombre_normalizado', $clave)
            ->where($donde)
            ->when($ignorarId, fn ($q) => $q->whereKeyNot($ignorarId))
            ->first();
    }

    /** " Está desactivado: actívalo en vez de registrarlo otra vez." o nada. */
    public function avisoSiInactivo(): string
    {
        return isset($this->activo) && !$this->activo ? ' Está desactivado: actívalo en vez de registrarlo otra vez.' : '';
    }
}
