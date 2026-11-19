<?php

namespace App\Http\Controllers;

use App\Models\CierreCaja;
use App\Models\Gasto;
use App\Services\CajaService;
use App\Support\Moneda;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Gastos pagados con el efectivo de la caja: su propio registro, con filtros y reportes.
 * Solo se anotan con la caja de hoy abierta y se restan del efectivo esperado al cerrar,
 * en la moneda con que se pagaron (CajaService).
 */
class GastoController extends Controller
{
    public function index(Request $request, CajaService $cajas)
    {
        $cajaPendiente = CierreCaja::pendienteAnterior();
        $cajaHoy = CierreCaja::whereDate('fecha', now()->toDateString())->first();
        $puedeAnotar = !$cajaPendiente && $cajaHoy && $cajaHoy->estado === 'ABIERTA';
        $disponible = $puedeAnotar ? $cajas->esperado($cajaHoy, $cajas->resumen($cajaHoy->fecha)) : null;

        return view('gastos.index', $this->datosListado($request) + compact('cajaPendiente', 'cajaHoy', 'puedeAnotar', 'disponible'));
    }

    public function show(Request $request, Gasto $gasto)
    {
        $gasto->load(['usuario', 'caja']);

        return view($request->ajax() ? 'gastos._ficha' : 'gastos.show', compact('gasto'));
    }

    public function exportarExcel(Request $request): StreamedResponse
    {
        $datos = $this->datosListado($request);

        return $this->descargarCsv('gastos_' . now()->format('Y-m-d_His') . '.csv', function (callable $fila) use ($datos) {
            $fila(['Fecha', 'Hora', 'Concepto', 'Tipo', 'Moneda', 'Monto', 'Equivalente $', 'Tasa', 'Anotado por']);

            foreach ($datos['gastos'] as $g) {
                $fila([
                    $g->fecha->format('Y-m-d'),
                    $g->fecha->format('h:i A'),
                    $g->concepto,
                    $g->nombreCategoria(),
                    $g->moneda,
                    number_format($g->monto_moneda, 2, '.', ''),
                    number_format($g->monto, 2, '.', ''),
                    $g->tasa_cambio ? number_format($g->tasa_cambio, 4, '.', '') : '',
                    $g->usuario->name ?? 'Sistema',
                ]);
            }
        });
    }

    public function exportarPDF(Request $request)
    {
        $pdf = Pdf::loadView('gastos.pdf', $this->datosListado($request));

        return $this->entregarPdf($pdf, 'Gastos_' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Gastos del periodo y sus totales, para la pantalla, el CSV y el PDF.
     * Sin fechas se muestra el mes en curso.
     */
    private function datosListado(Request $request): array
    {
        $request->validate([
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'tipo' => ['nullable', Rule::in(array_keys(Gasto::CATEGORIAS))],
        ]);

        $desde = $request->filled('fecha_inicio') ? Carbon::parse($request->fecha_inicio) : now()->startOfMonth();
        $hasta = $request->filled('fecha_fin') ? Carbon::parse($request->fecha_fin) : now();
        $tipo = $request->tipo;

        $gastos = Gasto::with('usuario', 'caja')
            ->whereDate('fecha', '>=', $desde->toDateString())
            ->whereDate('fecha', '<=', $hasta->toDateString())
            ->when($tipo, fn ($q) => $q->where('categoria', $tipo))
            ->latest('fecha')
            ->get();

        $porTipo = $gastos->groupBy('categoria')
            ->map(fn ($grupo, $clave) => [
                'nombre' => Gasto::CATEGORIAS[$clave] ?? ucfirst($clave),
                'cantidad' => $grupo->count(),
                'total' => round($grupo->sum('monto'), 2),
            ])
            ->sortByDesc('total')
            ->values();

        return [
            'gastos' => $gastos,
            'desde' => $desde,
            'hasta' => $hasta,
            'tipo' => $tipo,
            'totalUsd' => round($gastos->where('moneda', 'USD')->sum('monto_moneda'), 2),
            'totalVes' => round($gastos->where('moneda', 'VES')->sum('monto_moneda'), 2),
            'totalEquivalente' => round($gastos->sum('monto'), 2),
            'porTipo' => $porTipo,
        ];
    }

    public function store(Request $request, CajaService $cajas)
    {
        $request->validate([
            'concepto' => 'required|string|min:3|max:150',
            'categoria' => ['required', Rule::in(array_keys(Gasto::CATEGORIAS))],
            'moneda' => 'required|in:USD,VES',
            'monto' => 'required|numeric|min:0.01|max:9999999999',
        ]);

        if ($aviso = CierreCaja::avisoCajaPendiente()) {
            return back()->withErrors(['error' => $aviso])->withInput();
        }

        $moneda = $request->moneda;
        $tasa = Moneda::tasa();
        if ($moneda === 'VES' && $tasa <= 0) {
            return back()->withErrors(['error' => 'No hay tasa del BCV cargada, no se puede registrar un gasto en bolívares.'])->withInput();
        }

        $montoMoneda = round((float) $request->monto, 2);

        $error = DB::transaction(function () use ($request, $cajas, $moneda, $tasa, $montoMoneda) {
            // Se bloquea la caja para que dos gastos a la vez no saquen más efectivo del que hay
            $caja = CierreCaja::whereDate('fecha', now()->toDateString())->lockForUpdate()->first();
            if (!$caja || $caja->estado !== 'ABIERTA') {
                return 'Los gastos se anotan con la caja de hoy abierta. Ábrela (o pide que la reabran) y vuelve a intentarlo.';
            }

            $disponible = $cajas->esperado($caja, $cajas->resumen($caja->fecha))[$moneda === 'VES' ? 'ves' : 'usd'];
            if ($montoMoneda > $disponible + 0.001) {
                $texto = $moneda === 'VES' ? Moneda::ves($disponible) : Moneda::usd($disponible);
                return "El gasto es mayor que el efectivo en caja en esa moneda ({$texto}). Revisa el monto o la moneda.";
            }

            Gasto::create([
                'cierre_caja_id' => $caja->id,
                'user_id' => auth()->id(),
                'concepto' => trim($request->concepto),
                'categoria' => $request->categoria,
                'monto' => $moneda === 'VES' ? Moneda::aUsd($montoMoneda, $tasa) : $montoMoneda,
                'moneda' => $moneda,
                'monto_moneda' => $montoMoneda,
                'tasa_cambio' => $tasa > 0 ? $tasa : null,
                'fecha' => now(),
            ]);

            return null;
        });

        if ($error) {
            return back()->withErrors(['error' => $error])->withInput();
        }

        return redirect()->route('gastos.index')->with('success', 'Gasto anotado. Se restará del efectivo al cerrar la caja.');
    }

    /** Quita un gasto anotado por error, mientras su caja (la de hoy) siga abierta. */
    public function destroy(Gasto $gasto)
    {
        $caja = $gasto->caja;
        if ($caja->estado !== 'ABIERTA' || !$caja->esDeHoy()) {
            return back()->withErrors(['error' => 'Solo se pueden quitar gastos de hoy mientras la caja siga abierta.']);
        }

        $gasto->delete();

        return redirect()->route('gastos.index')->with('success', 'Gasto quitado. El monto vuelve a contarse como efectivo en caja.');
    }
}
