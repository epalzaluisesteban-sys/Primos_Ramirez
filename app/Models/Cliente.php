<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use \App\Models\Concerns\TieneFoto;
    use HasFactory;

    protected $table = 'clientes';

    protected $fillable = [
        'nombre',
        'foto',
        'telefono',
        'rif',
        'direccion',
        'limite_credito',
        'notas',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'limite_credito' => 'decimal:2',
    ];

    public function ventas()
    {
        return $this->hasMany(Venta::class);
    }

    public function abonos()
    {
        return $this->hasMany(Abono::class);
    }

    // Total vendido a crédito (fiado), sin contar ventas anuladas
    public function totalCredito(): float
    {
        return (float) $this->ventas()->where('tipo_pago', 'credito')->where('anulada', false)->sum('total');
    }

    // Total abonado (pagado) históricamente
    public function totalAbonado(): float
    {
        return (float) $this->abonos()->sum('monto');
    }

    // Saldo pendiente = lo vendido a crédito - lo abonado
    public function saldoPendiente(): float
    {
        return round($this->totalCredito() - $this->totalAbonado(), 2);
    }

    /**
     * Cuánto más se le puede fiar = límite de crédito − saldo pendiente (nunca negativo).
     * null = sin límite (se le puede fiar cualquier monto). Si ya se calculó el saldo, se pasa
     * para no repetir las consultas.
     */
    public function creditoDisponible(?float $saldo = null): ?float
    {
        if ($this->limite_credito === null) {
            return null;
        }

        $saldo ??= $this->saldoPendiente();

        return max(0.0, round((float) $this->limite_credito - $saldo, 2));
    }

    // Total comprado históricamente (contado + fiado), a diferencia de totalCredito()
    // que solo cuenta lo vendido a crédito.
    public function totalComprado(): float
    {
        return (float) $this->ventas()->where('anulada', false)->sum('total');
    }

    // Fecha de la última compra no anulada, o null si nunca ha comprado.
    public function ultimaCompra(): ?\Illuminate\Support\Carbon
    {
        $fecha = $this->ventas()->where('anulada', false)->max('fecha');
        return $fecha ? \Illuminate\Support\Carbon::parse($fecha) : null;
    }
}
