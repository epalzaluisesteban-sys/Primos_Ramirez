<?php

namespace App\Http\Controllers;

use App\Models\Abono;
use App\Models\CierreCaja;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Support\Moneda;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CajaController extends Controller
{
    /**
     * Efectivo que entra al cajón en el día, por moneda: ventas de contado (sin anuladas)
     * más abonos cobrados. Lo cobrado en $ y lo cobrado en Bs. se cuentan por separado, como
     * el efectivo físico. Las ventas a crédito no mueven efectivo hasta que el cliente abona.
     */
    private function resumenDelDia(): array
    {
        $hoy = now()->toDateString();

        $contado = Venta::whereDate('fecha', $hoy)->where('anulada', false)->where('tipo_pago', 'contado');
        $contadoUsd = (float) (clone $contado)->where('moneda_pago', 'USD')->sum('total');
        $contadoVes = (float) (clone $contado)->where('moneda_pago', 'VES')->sum('total_ves');
        $credito = (float) Venta::whereDate('fecha', $hoy)->where('anulada', false)->where('tipo_pago', 'credito')->sum('total');

        $abonos = Abono::whereDate('fecha', $hoy);
        $abonosUsd = (float) (clone $abonos)->where('moneda', 'USD')->sum('monto');
        $abonosVes = (float) (clone $abonos)->where('moneda', 'VES')->sum('monto_moneda');

        return [
            'contado_usd' => $contadoUsd,
            'contado_ves' => $contadoVes,
            'abonos_usd' => $abonosUsd,
            'abonos_ves' => $abonosVes,
            'credito' => $credito,
            'ingresos_usd' => round($contadoUsd + $abonosUsd, 2),
            'ingresos_ves' => round($contadoVes + $abonosVes, 2),
        ];
    }

    public function index()
    {
        $cajaHoy = CierreCaja::whereDate('fecha', now()->toDateString())->first();
        $resumen = $this->resumenDelDia();
        $historialCierres = CierreCaja::with('usuario')->latest('fecha')->take(10)->get();

        return view('caja.index', compact('cajaHoy', 'resumen', 'historialCierres'));
    }

    public function abrir(Request $request)
    {
        $request->validate([
            'monto_apertura' => 'required|numeric|min:0',
            'monto_apertura_ves' => 'nullable|numeric|min:0',
        ]);

        $hoy = now()->toDateString();

        $cajaExistente = CierreCaja::whereDate('fecha', $hoy)->first();
        if ($cajaExistente) {
            return back()->withErrors(['error' => 'Ya existe un registro de caja para la fecha de hoy.']);
        }

        CierreCaja::create([
            'user_id' => auth()->id(),
            'fecha' => $hoy,
            'monto_apertura' => $request->monto_apertura,
            'monto_apertura_ves' => $request->monto_apertura_ves ?? 0,
            'monto_ventas_sistema' => 0.00,
            'estado' => 'ABIERTA',
        ]);

        return redirect()->route('caja.index')->with('success', 'Jornada/Caja abierta correctamente.');
    }

    public function cerrar(Request $request, CierreCaja $caja)
    {
        $request->validate([
            'monto_efectivo_real' => 'required|numeric|min:0',
            'monto_efectivo_real_ves' => 'nullable|numeric|min:0',
            'observaciones' => 'nullable|string|max:500',
        ]);

        if ($caja->estado === 'CERRADA') {
            return back()->withErrors(['error' => 'Esta caja ya fue cerrada.']);
        }

        $resumen = $this->resumenDelDia();

        // Esperado por moneda = fondo inicial + efectivo ingresado en el día
        $esperadoUsd = round($caja->monto_apertura + $resumen['ingresos_usd'], 2);
        $esperadoVes = round($caja->monto_apertura_ves + $resumen['ingresos_ves'], 2);

        // Diferencia = efectivo contado - esperado (negativo = faltante)
        $realVes = (float) ($request->monto_efectivo_real_ves ?? 0);

        $caja->update([
            'monto_ventas_sistema' => $resumen['ingresos_usd'],
            'monto_ventas_sistema_ves' => $resumen['ingresos_ves'],
            'monto_efectivo_real' => $request->monto_efectivo_real,
            'monto_efectivo_real_ves' => $realVes,
            'diferencia' => round($request->monto_efectivo_real - $esperadoUsd, 2),
            'diferencia_ves' => round($realVes - $esperadoVes, 2),
            'estado' => 'CERRADA',
            'observaciones' => $request->observaciones,
        ]);

        return redirect()->route('caja.index')->with('success', 'Cierre de caja completado y guardado correctamente.');
    }

    public function exportarExcel(): StreamedResponse
    {
        $cierres = CierreCaja::with('usuario')->latest('fecha')->get();
        return $this->descargarCsv('historial_caja_' . now()->format('Y-m-d_His') . '.csv', function (callable $fila) use ($cierres) {
            $fila(['Fecha', 'Usuario', 'Apertura $', 'Apertura Bs.', 'Ingresos $', 'Ingresos Bs.', 'Efectivo Real $', 'Efectivo Real Bs.', 'Diferencia $', 'Diferencia Bs.', 'Estado', 'Observaciones']);

            foreach ($cierres as $c) {
                $fila([
                    $c->fecha,
                    $c->usuario->name ?? 'Sistema',
                    number_format($c->monto_apertura, 2, '.', ''),
                    number_format($c->monto_apertura_ves, 2, '.', ''),
                    number_format($c->monto_ventas_sistema ?? 0, 2, '.', ''),
                    number_format($c->monto_ventas_sistema_ves ?? 0, 2, '.', ''),
                    $c->monto_efectivo_real !== null ? number_format($c->monto_efectivo_real, 2, '.', '') : '',
                    $c->monto_efectivo_real_ves !== null ? number_format($c->monto_efectivo_real_ves, 2, '.', '') : '',
                    $c->diferencia !== null ? number_format($c->diferencia, 2, '.', '') : '',
                    $c->diferencia_ves !== null ? number_format($c->diferencia_ves, 2, '.', '') : '',
                    $c->estado,
                    $c->observaciones,
                ]);
            }

        });
    }

    public function exportarPDF()
    {
        $cierres = CierreCaja::with('usuario')->latest('fecha')->get();

        $pdf = Pdf::loadView('caja.pdf', compact('cierres'));
        return $pdf->download('Historial_Caja_' . now()->format('Y-m-d') . '.pdf');
    }
}
