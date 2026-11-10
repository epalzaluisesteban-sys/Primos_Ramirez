<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    use HasFactory;

    protected $table = 'proveedores';

    protected $fillable = [
        'nombre',
        'contacto',
        'telefono',
        'whatsapp',
        'email',
        'direccion',
        'notas',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function compras()
    {
        return $this->hasMany(Compra::class);
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
