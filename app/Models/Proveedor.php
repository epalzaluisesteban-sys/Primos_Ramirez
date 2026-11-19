<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    use HasFactory;
    use Concerns\TieneNombreUnico;

    protected $table = 'proveedores';

    protected $fillable = [
        'nombre',
        'contacto',
        'rif',
        'telefono',
        'whatsapp',
        'email',
        'direccion',
        'notas',
        'activo',
        'da_credito',
        'dias_credito',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'da_credito' => 'boolean',
        'dias_credito' => 'integer',
    ];

    public function compras()
    {
        return $this->hasMany(Compra::class);
    }

    /** Categorías de productos que surte (frutas, verduras, hortalizas…). */
    public function categorias()
    {
        return $this->belongsToMany(Categoria::class, 'categoria_proveedor')->orderBy('nombre');
    }

    public function pagos()
    {
        return $this->hasMany(PagoProveedor::class);
    }

    /**
     * Lo que se le debe = compras a crédito − pagos. Los pagos cubren primero las compras más viejas,
     * así se sabe cuál es el próximo vencimiento de lo que falta por pagar.
     *
     * @return array{saldo: float, comprado: float, pagado: float, vence_el: ?\Illuminate\Support\Carbon, vencido: bool}
     */
    public function estadoDeuda(): array
    {
        $compras = $this->compras()->where('tipo_pago', 'credito')->orderBy('fecha')->orderBy('id')->get(['total', 'vence_el']);
        $pagado = (float) $this->pagos()->sum('monto');
        $comprado = (float) $compras->sum('total');

        $porCubrir = $pagado;
        $venceEl = null;
        foreach ($compras as $compra) {
            $cubierto = min($porCubrir, (float) $compra->total);
            $porCubrir -= $cubierto;
            if ((float) $compra->total - $cubierto > 0.005 && $compra->vence_el && (!$venceEl || $compra->vence_el->lt($venceEl))) {
                $venceEl = $compra->vence_el;
            }
        }

        return [
            'saldo' => max(round($comprado - $pagado, 2), 0),
            'comprado' => round($comprado, 2),
            'pagado' => round($pagado, 2),
            'vence_el' => $venceEl,
            'vencido' => $venceEl !== null && $venceEl->lt(today()),
        ];
    }

    // Link "click to chat" de WhatsApp, o null si no tiene número registrado.
    public function whatsappUrl(): ?string
    {
        if (!$this->whatsapp) {
            return null;
        }
        // wa.me necesita el número internacional: 04121234567 → 584121234567
        $numero = \App\Support\Contacto::telefonoWhatsapp($this->whatsapp);
        return $numero ? "https://wa.me/{$numero}" : null;
    }

    public function totalComprado(): float
    {
        return (float) $this->compras()->sum('total');
    }

    public function ultimaCompra(): ?\Illuminate\Support\Carbon
    {
        $fecha = $this->compras()->max('fecha');
        return $fecha ? \Illuminate\Support\Carbon::parse($fecha) : null;
    }
}
