<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Dinero que sale del cajón durante el día (transporte, comida, servicios…). Se resta del
 * efectivo esperado de la caja en la moneda con que se pagó.
 */
class Gasto extends Model
{
    public const CATEGORIAS = [
        'transporte' => 'Transporte',
        'comida' => 'Comida',
        'servicios' => 'Servicios',
        'mantenimiento' => 'Mantenimiento',
        'otro' => 'Otro',
    ];

    protected $fillable = [
        'cierre_caja_id',
        'user_id',
        'concepto',
        'categoria',
        'monto',
        'moneda',
        'monto_moneda',
        'tasa_cambio',
        'fecha',
    ];

    protected $casts = [
        'fecha' => 'datetime',
    ];

    public function caja()
    {
        return $this->belongsTo(CierreCaja::class, 'cierre_caja_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function nombreCategoria(): string
    {
        return self::CATEGORIAS[$this->categoria] ?? ucfirst($this->categoria);
    }
}
