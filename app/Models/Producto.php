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
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
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

    // Unidades que se cuentan enteras (1, 2, 3…). Kg, Lb y Litro admiten hasta 3 decimales.
    const UNIDADES_ENTERAS = ['unidad', 'caja', 'atado', 'saco'];

    public static function unidadEsEntera(?string $unidad): bool
    {
        return in_array($unidad, self::UNIDADES_ENTERAS, true);
    }

    // ¿Las cantidades de este producto van sin decimales?
    public function usaCantidadEntera(): bool
    {
        return self::unidadEsEntera($this->unidad_medida);
    }

    // Texto bajo el campo de cantidad cuando todavía no se eligió un producto
    const AYUDA_SIN_PRODUCTO = 'Elige el producto: según su unidad de medida se permiten o no decimales.';

    // Explica la regla de cantidad según la unidad de medida (se muestra bajo el campo de cantidad)
    public static function ayudaParaUnidad(?string $unidad): string
    {
        $nombre = self::UNIDADES[$unidad] ?? (string) $unidad;

        return self::unidadEsEntera($unidad)
            ? "Se cuenta por {$nombre}: sin decimales (ej. 3)."
            : "Se mide en {$nombre}: admite decimales (ej. 2.5).";
    }

    public function ayudaCantidad(): string
    {
        return self::ayudaParaUnidad($this->unidad_medida);
    }

    /**
     * Revisa una cantidad para este producto. Devuelve el mensaje de error para el usuario,
     * o null si está bien (un producto por unidad no acepta 1.5).
     */
    public function errorCantidad(float|string $cantidad): ?string
    {
        if ($this->usaCantidadEntera() && !self::esEntero($cantidad)) {
            return "{$this->nombre} se cuenta por " . mb_strtolower($this->unidadLabel()) . ': escribe una cantidad sin decimales (ej. 1, 2, 3).';
        }

        return null;
    }

    public static function esEntero(float|string|null $valor): bool
    {
        return abs((float) $valor - round((float) $valor)) < 0.0005;
    }

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

    // Ajustes manuales de existencia (stock inicial, conteos, correcciones)
    public function ajustes()
    {
        return $this->hasMany(AjusteInventario::class);
    }

    // Tiene movimientos que forman parte del historial contable (no se puede eliminar)
    public function tieneHistorial(): bool
    {
        return $this->compras()->exists() || $this->detallesVenta()->exists() || $this->mermas()->exists();
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function unidadLabel(): string
    {
        return self::UNIDADES[$this->unidad_medida] ?? $this->unidad_medida;
    }

    public function tieneStockCritico(): bool
    {
        return $this->stock <= $this->stock_minimo;
    }

    // Sin existencias (la alerta más urgente). Los productos desactivados no generan alertas.
    public function scopeAgotados($query)
    {
        return $query->where('activo', true)->where('stock', '<=', 0);
    }

    // Bajo el mínimo pero todavía con algo de existencia (no se solapa con "agotados")
    public function scopeStockCritico($query)
    {
        return $query->where('activo', true)->whereColumn('stock', '<=', 'stock_minimo')->where('stock', '>', 0);
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