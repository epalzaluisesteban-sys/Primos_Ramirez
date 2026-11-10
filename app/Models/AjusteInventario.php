<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Cambio manual de existencia (stock inicial, conteo físico, corrección).
 * cantidad > 0 es entrada; cantidad < 0 es salida.
 */
class AjusteInventario extends Model
{
    protected $table = 'ajustes_inventario';

    protected $fillable = ['producto_id', 'user_id', 'cantidad', 'stock_anterior', 'stock_nuevo', 'motivo', 'fecha'];

    protected $casts = [
        'cantidad' => 'float',
        'stock_anterior' => 'float',
        'stock_nuevo' => 'float',
        'fecha' => 'datetime',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Registra un ajuste que lleva el stock del producto a $stockNuevo (si cambia). */
    public static function registrar(Producto $producto, float $stockNuevo, ?string $motivo, ?int $userId): ?self
    {
        $anterior = (float) $producto->stock;
        $diferencia = round($stockNuevo - $anterior, 3);

        if (abs($diferencia) < 0.0005) {
            return null;
        }

        $producto->update(['stock' => $stockNuevo]);

        return static::create([
            'producto_id' => $producto->id,
            'user_id' => $userId,
            'cantidad' => $diferencia,
            'stock_anterior' => $anterior,
            'stock_nuevo' => $stockNuevo,
            'motivo' => $motivo,
            'fecha' => now(),
        ]);
    }
}
