<?php

namespace App\Http\Controllers;

use App\Models\Merma;
use App\Models\Producto;
use App\Support\Cantidad;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MermaController extends Controller
{
    public function index()
    {
        $mermas = Merma::with(['producto.categoria', 'usuario'])->latest('fecha')->get();
        $productos = Producto::orderBy('nombre')->get();

        // Resumen del mes actual: cantidad y valor estimado (a precio de venta) perdido
        $inicioMes = now()->startOfMonth();
        $mermasMes = $mermas->filter(fn ($m) => $m->fecha >= $inicioMes);
        $valorPerdidoMes = $mermasMes->sum(fn ($m) => $m->valorEstimado());
        $cantidadMes = $mermasMes->sum('cantidad');

        // Distribución por motivo (para gráfico)
        $porMotivo = $mermas->groupBy('motivo')->map->count();

        return view('mermas.index', compact('mermas', 'productos', 'valorPerdidoMes', 'cantidadMes', 'porMotivo'));
    }

    public function exportarExcel(): StreamedResponse
    {
        $mermas = Merma::with(['producto', 'usuario'])->latest('fecha')->get();
        return $this->descargarCsv('mermas_' . now()->format('Y-m-d_His') . '.csv', function (callable $fila) use ($mermas) {
            $fila(['Fecha', 'Producto', 'Cantidad', 'Motivo', 'Observación', 'Valor Estimado Perdido', 'Registrado Por']);

            foreach ($mermas as $m) {
                $fila([
                    $m->fecha->format('Y-m-d H:i'),
                    $m->producto->nombre ?? 'Eliminado',
                    $m->cantidad,
                    $m->motivoLabel(),
                    $m->observacion,
                    number_format($m->valorEstimado(), 2, '.', ''),
                    $m->usuario->name ?? 'Sistema',
                ]);
            }

        });
    }

    public function exportarPDF()
    {
        $mermas = Merma::with(['producto', 'usuario'])->latest('fecha')->get();
        $valorTotalPerdido = $mermas->sum(fn ($m) => $m->valorEstimado());

        $pdf = Pdf::loadView('mermas.pdf', compact('mermas', 'valorTotalPerdido'));
        return $this->entregarPdf($pdf, 'Perdidas_' . now()->format('Y-m-d') . '.pdf');
    }

    public function show(Request $request, Merma $merma)
    {
        $merma->load(['producto', 'usuario']);

        return view($request->ajax() ? 'mermas._ficha' : 'mermas.show', compact('merma'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'producto_id' => 'required|integer|exists:productos,id',
            'cantidad' => 'required|numeric|min:0.001|max:999999',
            'motivo' => 'required|in:danado,vencido,robo_extravio,otro',
            'observacion' => 'nullable|required_if:motivo,otro|string|max:500',
        ], [
            'observacion.required_if' => 'Con el motivo "Otro", explica en la observación qué pasó.',
        ]);

        $error = DB::transaction(function () use ($request) {
            // Se bloquea la fila para que dos operaciones simultáneas no dejen el stock negativo
            $producto = Producto::lockForUpdate()->findOrFail($request->producto_id);

            // Productos por unidad (unidad, caja, atado, saco) se descartan en cantidades enteras
            if ($error = $producto->errorCantidad($request->cantidad)) {
                return ['cantidad' => $error];
            }

            if ($request->cantidad > $producto->stock) {
                return ['cantidad' => 'La cantidad a descartar (' . Cantidad::formato($request->cantidad) . ') supera la existencia de '
                    . $producto->nombre . ' (' . Cantidad::formato($producto->stock) . ' ' . $producto->unidadLabel() . ').'];
            }

            Merma::create([
                'producto_id' => $producto->id,
                'user_id' => auth()->id(),
                'cantidad' => $request->cantidad,
                'motivo' => $request->motivo,
                'observacion' => $request->observacion,
                'fecha' => now(),
            ]);

            $producto->decrement('stock', $request->cantidad);

            return null;
        });

        if ($error) {
            return back()->withErrors($error)->withInput();
        }

        return redirect()->route('mermas.index')->with('success', 'Pérdida registrada y existencia actualizada.');
    }
}
