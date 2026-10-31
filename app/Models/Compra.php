<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Compra extends Model
{
    use HasFactory;

    protected $fillable = [
        'producto_id',
        'user_id',
        'proveedor_id',
        'cantidad',
        'precio_compra',
        'total',
        'proveedor',
        'fecha',
    ];

    // Costo promedio ponderado por producto (SUM(cantidad*precio) / SUM(cantidad)), indexado por producto_id.
    protected $casts = ['cantidad' => 'float'];

    public static function costosPromedio()
    {
        return static::select(
                'producto_id',
                \Illuminate\Support\Facades\DB::raw('SUM(cantidad * precio_compra) / SUM(cantidad) as costo_promedio')
            )
            ->groupBy('producto_id')
            ->pluck('costo_promedio', 'producto_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function proveedorRef()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    // Nombre a mostrar: usa el proveedor registrado si existe, o el texto libre legado
    public function nombreProveedor(): string
    {
        return $this->proveedorRef->nombre ?? ($this->proveedor ?: 'Sin especificar');
    }
}