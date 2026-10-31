<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venta extends Model
{
    protected $fillable = [
        'user_id', 'cliente_id', 'tipo_pago', 'fecha', 'total',
        'anulada', 'anulada_at', 'motivo_anulacion', 'editada',
        'tasa_cambio', 'total_ves', 'moneda_pago',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'anulada_at' => 'datetime',
        'anulada' => 'boolean',
        'editada' => 'boolean',
    ];

    /**
     * Una venta solo puede editarse o anularse si sigue "reciente"
     * (fue registrada el mismo día) y aún no ha sido anulada.
     * Esto evita alterar el historial de cierres de caja ya cerrados.
     */
    public function puedeEditarse(): bool
    {
        return !$this->anulada && $this->fecha->isToday();
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

   public function detalles()
{
    return $this->hasMany(DetalleVenta::class, 'venta_id');
}
}