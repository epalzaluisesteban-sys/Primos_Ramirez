<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venta extends Model
{
    protected $fillable = [
        'user_id', 'cliente_id', 'tipo_pago', 'fecha', 'total',
        'anulada', 'anulada_at', 'motivo_anulacion', 'anulada_por', 'editada',
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
     * (fue registrada el mismo día), aún no ha sido anulada y la caja
     * de ese día no se ha cerrado (si no, el cuadre guardado quedaría mal).
     */
    public function puedeEditarse(): bool
    {
        return $this->motivoNoEditable() === null;
    }

    // Por qué no se puede editar ni eliminar (null si se puede). Se muestra al tocar los botones desactivados.
    public function motivoNoEditable(): ?string
    {
        if ($this->anulada) {
            return 'Esta venta ya está en la papelera.';
        }
        if (!$this->fecha->isToday()) {
            return 'Es una venta del ' . $this->fecha->format('d/m/Y') . '. Solo se pueden editar o eliminar las ventas de hoy, para no descuadrar la caja de días anteriores.';
        }
        if (CierreCaja::estaCerrada($this->fecha)) {
            return 'La caja de hoy ya se cerró. Reábrela en "Caja" para editar o eliminar esta venta.';
        }

        return null;
    }

    /**
     * Una venta de la papelera vuelve a la lista solo si es de hoy y la caja de hoy sigue abierta:
     * restaurar una de otro día cambiaría un cierre de caja ya cuadrado.
     */
    public function puedeRestaurarse(): bool
    {
        return $this->anulada && $this->fecha->isToday() && CierreCaja::estaAbierta($this->fecha);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    // Quién la mandó a la papelera
    public function eliminadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anulada_por');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleVenta::class, 'venta_id');
    }
}