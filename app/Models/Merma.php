<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Merma extends Model
{
    use HasFactory;

    protected $fillable = [
        'producto_id',
        'user_id',
        'cantidad',
        'motivo',
        'observacion',
        'fecha',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'cantidad' => 'float',
    ];

    const MOTIVOS = [
        'danado' => 'Producto dañado',
        'vencido' => 'Producto vencido',
        'robo_extravio' => 'Robo / extravío',
        'otro' => 'Otro',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    // Valor perdido estimado a precio de venta actual del producto
    public function valorEstimado(): float
    {
        return round($this->cantidad * ($this->producto->precio ?? 0), 2);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function motivoLabel(): string
    {
        return self::MOTIVOS[$this->motivo] ?? $this->motivo;
    }
}
