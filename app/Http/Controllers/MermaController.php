<?php

namespace App\Http\Controllers;

use App\Models\Merma;
use App\Models\Producto;
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
        return $pdf->download('Perdidas_' . now()->format('Y-m-d') . '.pdf');
    }

    public function store(Request $request)
    {
        $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'cantidad' => 'required|numeric|min:0.001',
            'motivo' => 'required|in:danado,vencido,robo_extravio,otro',
            'observacion' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            // Se bloquea la fila para que dos operaciones simultáneas no dejen el stock negativo
            $producto = Producto::lockForUpdate()->findOrFail($request->producto_id);

            if ($request->cantidad > $producto->stock) {
                DB::rollBack();
                return back()->withErrors(['error' => 'La cantidad a descartar (' . $request->cantidad . ') supera el stock disponible (' . $producto->stock . ').'])->withInput();
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

            DB::commit();
            return redirect()->route('mermas.index')->with('success', 'Pérdida registrada y existencia actualizada.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al registrar la pérdida: ' . $e->getMessage()]);
        }
    }
}
