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

    // Fechas (Y-m-d) ya consultadas en este request, para no repetir la consulta en listados.
    private static array $cerradas = [];
    private static array $abiertas = [];

    /**
     * ¿Hay una caja ABIERTA ese día? Las ventas solo se registran con la caja abierta, para que
     * todo lo cobrado entre en un cuadre con su fondo inicial.
     */
    public static function estaAbierta(\DateTimeInterface|string $fecha): bool
    {
        $dia = \Illuminate\Support\Carbon::parse($fecha)->toDateString();

        return self::$abiertas[$dia] ??= static::whereDate('fecha', $dia)->where('estado', 'ABIERTA')->exists();
    }

    /**
     * ¿La caja de ese día ya se cerró? Después del cierre no deben entrar ni cambiar ventas
     * ni abonos de ese día, porque el cuadre guardado quedaría desactualizado.
     */
    public static function estaCerrada(\DateTimeInterface|string $fecha): bool
    {
        $dia = \Illuminate\Support\Carbon::parse($fecha)->toDateString();

        return self::$cerradas[$dia] ??= static::whereDate('fecha', $dia)->where('estado', 'CERRADA')->exists();
    }

    public static function olvidarEstados(): void
    {
        self::$cerradas = [];
        self::$abiertas = [];
    }

    protected static function booted(): void
    {
        static::saved(fn () => self::olvidarEstados());
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}