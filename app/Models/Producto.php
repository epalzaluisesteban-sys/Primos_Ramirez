<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use \App\Models\Concerns\TieneFoto;
    use HasFactory;

    protected $fillable = [
        'categoria_id',
        'nombre',
        'foto',
        'marca',
        'ubicacion',
        'codigo_barras',
        'codigo_qr',
        'unidad_medida',
        'precio',
        'precio_compra',
        'stock',
        'stock_minimo',
        'stock_maximo',
        'fecha_vencimiento',
    ];

    protected $casts = [
        'fecha_vencimiento' => 'date',
        'stock' => 'float',
        'stock_minimo' => 'float',
        'stock_maximo' => 'float',
    ];

    const UNIDADES = [
        'unidad' => 'Unidad',
        'kg' => 'Kilogramo (Kg)',
        'lb' => 'Libra (Lb)',
        'caja' => 'Caja',
        'atado' => 'Atado',
        'saco' => 'Saco',
        'litro' => 'Litro',
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    // Relación con Entradas de Mercancía
    public function compras()
    {
        return $this->hasMany(Compra::class);
    }

    // Relación con Salidas por Venta
    public function detallesVenta()
    {
        return $this->hasMany(DetalleVenta::class);
    }

    // Relación con Mermas / Pérdidas
    public function mermas()
    {
        return $this->hasMany(Merma::class);
    }

    public function unidadLabel(): string
    {
        return self::UNIDADES[$this->unidad_medida] ?? $this->unidad_medida;
    }

    public function tieneStockCritico(): bool
    {
        return $this->stock <= $this->stock_minimo;
    }

    // Sin existencias (la alerta más urgente)
    public function scopeAgotados($query)
    {
        return $query->where('stock', '<=', 0);
    }

    // Bajo el mínimo pero todavía con algo de existencia (no se solapa con "agotados")
    public function scopeStockCritico($query)
    {
        return $query->whereColumn('stock', '<=', 'stock_minimo')->where('stock', '>', 0);
    }

    public function diasParaVencer(): ?int
    {
        if (!$this->fecha_vencimiento) {
            return null;
        }
        return now()->startOfDay()->diffInDays($this->fecha_vencimiento->startOfDay(), false);
    }

    // Margen calculado a partir del precio de compra de referencia, NO almacenado
    // (se derivaría de precio y precio_compra, así que guardarlo lo desincronizaría).
    public function porcentajeGanancia(): ?float
    {
        if (!$this->precio_compra || $this->precio_compra <= 0) {
            return null;
        }
        return round((($this->precio - $this->precio_compra) / $this->precio_compra) * 100, 1);
    }
}