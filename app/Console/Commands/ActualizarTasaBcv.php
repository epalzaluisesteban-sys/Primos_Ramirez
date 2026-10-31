<?php

namespace App\Console\Commands;

use App\Services\TasaCambioService;
use Illuminate\Console\Command;

class ActualizarTasaBcv extends Command
{
    protected $signature = 'tasa:actualizar';

    protected $description = 'Consulta la tasa oficial del BCV (Bs. por USD) y la guarda';

    public function handle(TasaCambioService $servicio): int
    {
        $tasa = $servicio->actualizarDesdeApi();

        if (!$tasa) {
            $this->error('No se pudo obtener la tasa del BCV (revisa la conexión a internet).');
            return self::FAILURE;
        }

        $this->info("Tasa BCV al {$tasa->fecha->format('d/m/Y')}: Bs. " . number_format($tasa->tasa, 4, ',', '.') . ' por $1');
        return self::SUCCESS;
    }
}
