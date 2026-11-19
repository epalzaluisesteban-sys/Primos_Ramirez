<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pago a un proveedor por compras a crédito. Si salió del efectivo de la caja (`cierre_caja_id`),
 * se resta en el cuadre de ese día.
 */
class PagoProveedor extends Model
{
    protected $table = 'pagos_proveedor';

    protected $fillable = [
        'proveedor_id',
        'user_id',
        'cierre_caja_id',
        'monto',
        'moneda',
        'monto_moneda',
        'tasa_cambio',
        'notas',
        'fecha',
    ];

    protected $casts = [
        'fecha' => 'datetime',
    ];

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function salioDeCaja(): bool
    {
        return $this->cierre_caja_id !== null;
    }
}
