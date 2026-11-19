<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Qué se considera "el mismo dato" en productos, categorías, proveedores y clientes, y cómo se corrigen
 * los repetidos que ya estaban guardados (al migrar o al restaurar un respaldo viejo) sin borrar nada:
 * al nombre repetido se le agrega "(repetido N)" y el documento repetido se quita y se anota en las notas.
 */
class Unicidad
{
    /** "  Tomáte   CRIOLLO " → "tomate criollo". Sirve para comparar, no para mostrar. */
    public static function normalizar(?string $texto): string
    {
        return Str::of((string) $texto)->ascii()->lower()->squish()->toString();
    }

    /** El producto se identifica por nombre + marca (y su unidad, que va en otra columna del índice). */
    public static function claveProducto(?string $nombre, ?string $marca): string
    {
        return self::normalizar($nombre . ' ' . $marca);
    }

    /**
     * Devuelve las filas (arreglos columna => valor, en orden de id) con `nombre_normalizado` lleno y sin
     * repetidos. Gana el primero que se registró.
     */
    public static function corregir(string $tabla, array $filas): array
    {
        return match ($tabla) {
            'productos' => self::corregirProductos($filas),
            'categorias' => self::corregirNombres($filas, fn ($f) => self::normalizar($f['nombre']), 'repetida'),
            'proveedores' => self::corregirDocumentos(
                self::corregirNombres($filas, fn ($f) => self::normalizar($f['nombre']), 'repetido'), 'proveedor'
            ),
            'clientes' => self::corregirDocumentos($filas, 'cliente'),
            default => $filas,
        };
    }

    private static function corregirProductos(array $filas): array
    {
        foreach ($filas as &$fila) {
            $codigo = isset($fila['codigo_barras']) ? preg_replace('/\s+/', '', (string) $fila['codigo_barras']) : '';
            $fila['codigo_barras'] = $codigo === '' ? null : $codigo;
        }
        unset($fila);

        $filas = self::corregirNombres(
            $filas,
            fn ($f) => self::claveProducto($f['nombre'], $f['marca'] ?? null),
            'repetido',
            fn ($f) => $f['unidad_medida'] ?? ''
        );

        // Un código de barras es de un solo producto: el repetido se quita del que llegó después
        $usados = [];
        foreach ($filas as &$fila) {
            if ($fila['codigo_barras'] !== null && isset($usados[$fila['codigo_barras']])) {
                $fila['codigo_barras'] = null;
            } elseif ($fila['codigo_barras'] !== null) {
                $usados[$fila['codigo_barras']] = true;
            }
        }

        return $filas;
    }

    /**
     * Llena `nombre_normalizado` y, si dos filas tienen la misma clave (dentro del mismo grupo, p. ej. la
     * unidad del producto), le agrega " (repetido 2)", " (repetido 3)"… al nombre de las que llegaron después.
     */
    private static function corregirNombres(array $filas, callable $clave, string $palabra, ?callable $grupo = null): array
    {
        $usadas = [];
        foreach ($filas as &$fila) {
            $prefijo = $grupo ? $grupo($fila) . '|' : '';
            $base = $fila['nombre'] = preg_replace('/\s+/', ' ', trim((string) $fila['nombre']));
            for ($n = 2; isset($usadas[$prefijo . $clave($fila)]); $n++) {
                $fila['nombre'] = "{$base} ({$palabra} {$n})";
            }
            $fila['nombre_normalizado'] = $clave($fila);
            $usadas[$prefijo . $fila['nombre_normalizado']] = true;
        }

        return $filas;
    }

    /** Cédula/RIF repetido: se quita de la fila que llegó después y se deja anotado en sus notas. */
    private static function corregirDocumentos(array $filas, string $quien): array
    {
        $duenos = [];
        foreach ($filas as &$fila) {
            $rif = $fila['rif'] ?? null;
            if ($rif === null || $rif === '') {
                $fila['rif'] = null;
                continue;
            }
            if (isset($duenos[$rif])) {
                [$id, $nombre] = $duenos[$rif];
                $nota = "[Tenía la cédula/RIF {$rif}, que ya es del {$quien} {$nombre} (#{$id}). Escribe la correcta.]";
                $fila['notas'] = trim(($fila['notas'] ?? '') . "\n" . $nota);
                $fila['rif'] = null;
            } else {
                $duenos[$rif] = [$fila['id'] ?? '?', $fila['nombre']];
            }
        }

        return $filas;
    }
}
