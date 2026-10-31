<?php

namespace App\Http\Controllers;

use App\Services\ActividadService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HistorialController extends Controller
{
    public function index(Request $request)
    {
        $datos = $this->datosHistorial($request);

        return view('historial.index', $datos);
    }

    public function exportarExcel(Request $request): StreamedResponse
    {
        $datos = $this->datosHistorial($request);
        return $this->descargarCsv('historial_' . now()->format('Y-m-d_His') . '.csv', function (callable $fila) use ($datos) {
            $fila(['Fecha', 'Tipo', 'Detalle', 'Usuario', 'Monto']);
            foreach ($datos['eventos'] as $e) {
                $fila([
                    $e['fecha']->format('Y-m-d H:i'),
                    $e['tipo'],
                    $e['texto'],
                    $e['usuario'],
                    $e['monto'] !== null ? number_format($e['monto'], 2, '.', '') : '',
                ]);
            }

        });
    }

    public function exportarPDF(Request $request)
    {
        $datos = $this->datosHistorial($request);

        $pdf = Pdf::loadView('historial.pdf', $datos);
        return $pdf->download('Historial_' . now()->format('Y-m-d') . '.pdf');
    }

    // Bitácora global con filtro de fechas (misma línea de tiempo que el feed del Dashboard).
    private function datosHistorial(Request $request): array
    {
        $desde = $request->input('desde', now()->subDays(30)->toDateString());
        $hasta = $request->input('hasta', now()->toDateString());

        $inicio = \Carbon\Carbon::parse($desde)->startOfDay();
        $fin = \Carbon\Carbon::parse($hasta)->endOfDay();

        $eventos = app(ActividadService::class)->eventos($inicio, $fin);

        return compact('eventos', 'desde', 'hasta');
    }
}
