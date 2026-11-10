<?php

namespace App\Support;

/**
 * Formato de cantidades y existencias para mostrar en pantalla, PDF y tickets.
 * Las columnas guardan 3 decimales (3.000), pero se muestran sin ceros de sobra:
 * 3.000 → "3" (productos por unidad, caja, atado, saco) y 2.500 → "2.5" (kg, lb, litro).
 * Uso en Blade: @cantidad($compra->cantidad)
 */
class Cantidad
{
    public static function formato(float|int|string|null $valor): string
    {
        $texto = number_format(round((float) $valor, 3), 3, '.', '');
        $texto = rtrim(rtrim($texto, '0'), '.');

        return $texto === '-0' ? '0' : $texto;
    }
}
