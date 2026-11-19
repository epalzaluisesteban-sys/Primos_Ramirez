<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

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
        'monto_gastos',
        'monto_gastos_ves',
        'monto_efectivo_real',
        'monto_efectivo_real_ves',
        'diferencia',
        'diferencia_ves',
        'estado',
        'cerrada_por',
        'cerrada_at',
        'observaciones',
    ];

    protected $casts = [
        'cerrada_at' => 'datetime',
    ];

    // Fechas (Y-m-d) ya consultadas en este request, para no repetir la consulta en listados.
    private static array $cerradas = [];
    private static array $abiertas = [];
    private static self|false|null $pendiente = null;

    /**
     * ¿Hay una caja ABIERTA ese día? Las ventas solo se registran con la caja abierta, para que
     * todo lo cobrado entre en un cuadre con su fondo inicial.
     */
    public static function estaAbierta(\DateTimeInterface|string $fecha): bool
    {
        $dia = Carbon::parse($fecha)->toDateString();

        return self::$abiertas[$dia] ??= static::whereDate('fecha', $dia)->where('estado', 'ABIERTA')->exists();
    }

    /**
     * ¿La caja de ese día ya se cerró? Después del cierre no deben entrar ni cambiar ventas
     * ni abonos de ese día, porque el cuadre guardado quedaría desactualizado.
     */
    public static function estaCerrada(\DateTimeInterface|string $fecha): bool
    {
        $dia = Carbon::parse($fecha)->toDateString();

        return self::$cerradas[$dia] ??= static::whereDate('fecha', $dia)->where('estado', 'CERRADA')->exists();
    }

    /**
     * La caja más vieja de un día anterior que quedó sin cerrar. Mientras exista no se abre la de hoy
     * ni se registran ventas, abonos o gastos: primero hay que cuadrar ese día.
     */
    public static function pendienteAnterior(): ?self
    {
        if (self::$pendiente === null) {
            self::$pendiente = static::where('estado', 'ABIERTA')
                ->whereDate('fecha', '<', now()->toDateString())
                ->orderBy('fecha')
                ->first() ?? false;
        }

        return self::$pendiente ?: null;
    }

    /** Mensaje para quien intenta vender o cobrar con una caja anterior abierta; null si no hay ninguna. */
    public static function avisoCajaPendiente(): ?string
    {
        $caja = self::pendienteAnterior();

        return $caja
            ? 'La caja del ' . $caja->fechaCorta() . ' quedó abierta. Ciérrala en "Caja" (cuenta el efectivo de ese día) y después abre la de hoy.'
            : null;
    }

    public static function olvidarEstados(): void
    {
        self::$cerradas = [];
        self::$abiertas = [];
        self::$pendiente = null;
    }

    protected static function booted(): void
    {
        static::saved(fn () => self::olvidarEstados());
    }

    public function esDeHoy(): bool
    {
        return Carbon::parse($this->fecha)->isToday();
    }

    public function fechaCorta(): string
    {
        return Carbon::parse($this->fecha)->format('d/m/Y');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cerradaPor()
    {
        return $this->belongsTo(User::class, 'cerrada_por');
    }

    public function gastos()
    {
        return $this->hasMany(Gasto::class);
    }
}
