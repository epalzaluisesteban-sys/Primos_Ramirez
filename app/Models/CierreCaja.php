<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CierreCaja extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'fecha',
        'monto_apertura',
        'monto_apertura_ves',
        'monto_ventas_sistema',
        'monto_ventas_sistema_ves',
        'monto_efectivo_real',
        'monto_efectivo_real_ves',
        'diferencia',
        'diferencia_ves',
        'estado',
        'observaciones',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}