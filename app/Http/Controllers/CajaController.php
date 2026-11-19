<?php

namespace App\Http\Controllers;

use App\Models\CierreCaja;
use App\Models\Gasto;
use App\Services\CajaService;
use App\Support\Moneda;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CajaController extends Controller
{
    public function __construct(private CajaService $cajas)
    {
    }

    public function index()
    {
        // Si quedó abierta la caja de un día anterior, el panel muestra esa para cerrarla primero
        $cajaPendiente = CierreCaja::pendienteAnterior();
        $caja = $cajaPendiente ?? CierreCaja::whereDate('fecha', now()->toDateString())->first();

        $dia = $caja->fecha ?? now();
        $resumen = $this->cajas->resumen($dia);
        $esperado = $caja ? $this->cajas->esperado($caja, $resumen) : null;
        $gastos = Gasto::with('usuario')->whereDate('fecha', Carbon::parse($dia)->toDateString())->latest('fecha')->get();
        $historialCierres = CierreCaja::with('usuario')->latest('fecha')->take(10)->get();

        return view('caja.index', compact('caja', 'cajaPendiente', 'resumen', 'esperado', 'gastos', 'historialCierres'));
    }

    public function abrir(Request $request)
    {
        $request->validate([
            'monto_apertura' => 'required|numeric|min:0|max:99999999',
            'monto_apertura_ves' => 'nullable|numeric|min:0|max:9999999999',
        ]);

        if ($aviso = CierreCaja::avisoCajaPendiente()) {
            return back()->withErrors(['error' => $aviso]);
        }

        $hoy = now()->toDateString();
        $yaExiste = 'Ya existe un registro de caja para la fecha de hoy.';

        if (CierreCaja::whereDate('fecha', $hoy)->exists()) {
            return back()->withErrors(['error' => $yaExiste]);
        }

        // Si dos envíos llegan a la vez (doble clic), el índice único de la fecha frena el segundo
        try {
            CierreCaja::create([
                'user_id' => auth()->id(),
                'fecha' => $hoy,
                'monto_apertura' => $request->monto_apertura,
                'monto_apertura_ves' => $request->monto_apertura_ves ?? 0,
                'monto_ventas_sistema' => 0.00,
                'estado' => 'ABIERTA',
            ]);
        } catch (UniqueConstraintViolationException) {
            return back()->withErrors(['error' => $yaExiste]);
        }

        return redirect()->route('caja.index')->with('success', 'Jornada/Caja abierta correctamente.');
    }

    public function cerrar(Request $request, CierreCaja $caja)
    {
        $request->validate([
            'monto_efectivo_real' => 'required|numeric|min:0|max:99999999',
            'monto_efectivo_real_ves' => 'nullable|numeric|min:0|max:9999999999',
            'observaciones' => 'nullable|string|max:500',
        ]);

        if ($caja->estado === 'CERRADA') {
            return back()->withErrors(['error' => 'Esta caja ya fue cerrada.']);
        }

        // Se cuadra contra los movimientos del día de ESA caja (puede cerrarse al día siguiente)
        $resumen = $this->cajas->resumen($caja->fecha);
        $esperado = $this->cajas->esperado($caja, $resumen);

        // Diferencia = efectivo contado - esperado (negativo = faltante)
        $realVes = (float) ($request->monto_efectivo_real_ves ?? 0);

        $caja->update([
            'monto_ventas_sistema' => $resumen['ingresos_usd'],
            'monto_ventas_sistema_ves' => $resumen['ingresos_ves'],
            // Todo lo que salió del cajón: gastos del día y pagos a proveedores en efectivo
            'monto_gastos' => $resumen['salidas_usd'],
            'monto_gastos_ves' => $resumen['salidas_ves'],
            'monto_efectivo_real' => $request->monto_efectivo_real,
            'monto_efectivo_real_ves' => $realVes,
            'diferencia' => round($request->monto_efectivo_real - $esperado['usd'], 2),
            'diferencia_ves' => round($realVes - $esperado['ves'], 2),
            'estado' => 'CERRADA',
            'cerrada_por' => auth()->id(),
            'cerrada_at' => now(),
            // Si la caja se reabrió antes, se conserva la nota de cada reapertura
            'observaciones' => trim(($caja->observaciones ? $caja->observaciones . "\n" : '') . $request->observaciones) ?: null,
        ]);

        $otraPendiente = CierreCaja::pendienteAnterior() ? ' Todavía queda otra caja de un día anterior sin cerrar.' : '';

        return redirect()->route('caja.index')
            ->with('success', 'Caja del ' . $caja->fechaCorta() . ' cerrada. Ya puedes descargar el reporte del día.' . $otraPendiente);
    }

    /**
     * Reabre la caja de HOY si se cerró por error, para seguir registrando ventas y abonos.
     * El cierre anterior queda anotado en las observaciones (quién, cuándo y qué se contó);
     * al cerrarla de nuevo se vuelve a cuadrar con todos los movimientos del día.
     */
    public function reabrir(CierreCaja $caja)
    {
        if ($caja->estado !== 'CERRADA') {
            return back()->withErrors(['error' => 'Esta caja ya está abierta.']);
        }
        if (!$caja->esDeHoy()) {
            return back()->withErrors(['error' => 'Solo se puede reabrir la caja de hoy. Las de días anteriores quedan como se cerraron.']);
        }

        $nota = sprintf(
            '[Reabierta el %s por %s. Cierre anterior: contado %s / %s, diferencia %s / %s]',
            now()->format('d/m/Y h:i A'),
            auth()->user()->name,
            Moneda::usd($caja->monto_efectivo_real),
            Moneda::ves($caja->monto_efectivo_real_ves),
            Moneda::usd($caja->diferencia),
            Moneda::ves($caja->diferencia_ves)
        );

        $caja->update([
            'estado' => 'ABIERTA',
            'monto_efectivo_real' => null,
            'monto_efectivo_real_ves' => null,
            'diferencia' => 0,
            'diferencia_ves' => null,
            'cerrada_por' => null,
            'cerrada_at' => null,
            'observaciones' => trim(($caja->observaciones ? $caja->observaciones . "\n" : '') . $nota),
        ]);

        return redirect()->route('caja.index')->with('success', 'Caja reabierta. Ya puedes registrar ventas y abonos; al terminar, ciérrala de nuevo.');
    }

    /** Detalle de una caja del historial: el listado lo pide por fetch y lo muestra en la ventana flotante. */
    public function show(Request $request, CierreCaja $caja)
    {
        $datos = $this->cajas->reporte($caja);

        return view($request->ajax() ? 'caja._detalle' : 'caja.show', $datos);
    }

    /** PDF con todo lo que se hizo en el día de la caja: cuadre, ventas, productos, fiados, abonos y gastos. */
    public function reporte(CierreCaja $caja)
    {
        $pdf = Pdf::loadView('caja.reporte-pdf', $this->cajas->reporte($caja));

        return $this->entregarPdf($pdf, 'Caja_' . Carbon::parse($caja->fecha)->format('Y-m-d') . '.pdf');
    }

    /** Ticket de 80 mm con el cierre del día y lo vendido por producto. */
    public function ticket(CierreCaja $caja)
    {
        $datos = $this->cajas->reporte($caja);
        $lineas = 24 + 2 * $datos['productos']->count() + ($datos['gastos']->isEmpty() ? 0 : 2 + $datos['gastos']->count());

        return $this->entregarTicket('caja.ticket', $datos, $lineas, 'Cierre_caja_' . Carbon::parse($caja->fecha)->format('Y-m-d') . '.pdf');
    }

    public function exportarExcel(): StreamedResponse
    {
        $cierres = CierreCaja::with('usuario')->latest('fecha')->get();
        return $this->descargarCsv('historial_caja_' . now()->format('Y-m-d_His') . '.csv', function (callable $fila) use ($cierres) {
            $fila(['Fecha', 'Usuario', 'Apertura $', 'Apertura Bs.', 'Ingresos $', 'Ingresos Bs.', 'Gastos $', 'Gastos Bs.', 'Efectivo Real $', 'Efectivo Real Bs.', 'Diferencia $', 'Diferencia Bs.', 'Estado', 'Observaciones']);

            foreach ($cierres as $c) {
                $fila([
                    $c->fecha,
                    $c->usuario->name ?? 'Sistema',
                    number_format($c->monto_apertura, 2, '.', ''),
                    number_format($c->monto_apertura_ves, 2, '.', ''),
                    number_format($c->monto_ventas_sistema ?? 0, 2, '.', ''),
                    number_format($c->monto_ventas_sistema_ves ?? 0, 2, '.', ''),
                    number_format($c->monto_gastos ?? 0, 2, '.', ''),
                    number_format($c->monto_gastos_ves ?? 0, 2, '.', ''),
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
        return $this->entregarPdf($pdf, 'Historial_Caja_' . now()->format('Y-m-d') . '.pdf');
    }
}
