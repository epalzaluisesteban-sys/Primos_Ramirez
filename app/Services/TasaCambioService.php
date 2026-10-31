<?php

namespace App\Services;

use App\Models\TasaCambio;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Tasa oficial del BCV (Bs. por USD).
 *
 * Se consulta la API pública ve.dolarapi.com (espejo de la tasa oficial publicada por el BCV).
 * Si no hay internet o la API falla, se sigue usando la última tasa guardada, y siempre
 * se puede fijar una tasa manual desde la pantalla "Tasa de cambio".
 */
class TasaCambioService
{
    const URL_API = 'https://ve.dolarapi.com/v1/dolares/oficial';

    // Tras un intento fallido no se vuelve a consultar durante este tiempo (segundos)
    const ESPERA_TRAS_FALLO = 900;

    private static ?TasaCambio $actual = null;

    /** Última tasa registrada (null si nunca se ha cargado una). */
    public static function actual(): ?TasaCambio
    {
        return self::$actual ??= TasaCambio::orderByDesc('fecha')->orderByDesc('id')->first();
    }

    /** Valor numérico de la tasa vigente; 0 si no hay ninguna. */
    public static function valor(): float
    {
        return (float) (self::actual()->tasa ?? 0);
    }

    public static function olvidar(): void
    {
        self::$actual = null;
    }

    /**
     * Consulta el BCV si la tasa guardada no es de hoy. Es liviano: usa un candado en caché
     * para no pedir la API en cada página y no bloquear si no hay internet.
     */
    public function refrescarSiHaceFalta(): void
    {
        $ultima = self::actual();

        if ($ultima && $ultima->fecha->isToday() && $ultima->fuente === 'manual') {
            return; // la tasa manual de hoy manda
        }
        if ($ultima && $ultima->fecha->isToday() && $ultima->esAutomatica()) {
            return;
        }

        if (!Cache::add('tasa_bcv_intento', 1, self::ESPERA_TRAS_FALLO)) {
            return;
        }

        $this->actualizarDesdeApi();
    }

    /** Pide la tasa oficial a la API y la guarda. Devuelve la tasa o null si falló. */
    public function actualizarDesdeApi(): ?TasaCambio
    {
        try {
            $respuesta = Http::timeout(4)->acceptJson()->get(self::URL_API);
            $tasa = (float) ($respuesta->json('promedio') ?? 0);

            if (!$respuesta->successful() || $tasa <= 0) {
                return null;
            }

            $registro = $this->guardar($tasa, 'BCV', null);
            Cache::forget('tasa_bcv_intento');

            return $registro;
        } catch (\Throwable $e) {
            Log::warning('No se pudo consultar la tasa del BCV: ' . $e->getMessage());
            return null;
        }
    }

    /** Guarda (o reemplaza la de hoy) una tasa. */
    public function guardar(float $tasa, string $fuente, ?int $userId): TasaCambio
    {
        $registro = TasaCambio::updateOrCreate(
            ['fecha' => now()->toDateString()],
            ['tasa' => round($tasa, 4), 'fuente' => $fuente, 'user_id' => $userId]
        );

        self::olvidar();

        return $registro;
    }
}
