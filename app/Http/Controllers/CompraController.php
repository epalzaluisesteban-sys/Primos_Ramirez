<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\Producto;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompraController extends Controller
{
    public function index()
    {
        $compras = Compra::with(['producto', 'usuario', 'proveedorRef'])->latest()->get();
        $productos = Producto::activos()->orderBy('nombre')->get();
        $proveedores = Proveedor::where('activo', true)->orderBy('nombre')->get();

        return view('compras.index', compact('compras', 'productos', 'proveedores'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'producto_id' => ['required', \Illuminate\Validation\Rule::exists('productos', 'id')->where('activo', true)],
            'cantidad' => 'required|numeric|min:0.001',
            'precio_compra' => 'required|numeric|min:0.01',
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'proveedor' => 'nullable|string|max:255',
        ]);

        // Productos por unidad (unidad, caja, atado, saco) se reciben en cantidades enteras
        if ($error = Producto::findOrFail($request->producto_id)->errorCantidad($request->cantidad)) {
            return back()->withErrors(['cantidad' => $error])->withInput();
        }

        DB::beginTransaction();

        try {
            $total = round($request->cantidad * $request->precio_compra, 2);

            $nombreProveedor = $request->proveedor_id
                ? Proveedor::find($request->proveedor_id)->nombre
                : ($request->proveedor ?? 'Proveedor General');

            // 1. Registrar la compra
            Compra::create([
                'producto_id' => $request->producto_id,
                'user_id' => auth()->id(),
                'proveedor_id' => $request->proveedor_id,
                'cantidad' => $request->cantidad,
                'precio_compra' => $request->precio_compra,
                'total' => $total,
                'proveedor' => $nombreProveedor,
                'fecha' => now(),
            ]);

            // 2. Incrementar el stock del producto automáticamente
            $producto = Producto::findOrFail($request->producto_id);
            $producto->increment('stock', $request->cantidad);

            DB::commit();

            return redirect()->route('compras.index')->with('success', 'Entrada de mercancía registrada. Stock actualizado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al registrar la entrada de mercancía: ' . $e->getMessage()]);
        }
    }

    /**
     * Exporta el historial de compras a un archivo CSV compatible con Excel.
     */
    public function exportarExcel(): StreamedResponse
    {
        $compras = Compra::with(['producto', 'usuario', 'proveedorRef'])->latest()->get();
        return $this->descargarCsv('compras_' . now()->format('Y-m-d_His') . '.csv', function (callable $fila) use ($compras) {
            $fila(['Fecha', 'Producto', 'Cantidad', 'Costo Unitario', 'Total', 'Proveedor', 'Registrado Por']);

            foreach ($compras as $compra) {
                $fila([
                    $compra->fecha,
                    $compra->producto->nombre ?? 'Eliminado',
                    $compra->cantidad,
                    number_format($compra->precio_compra, 2, '.', ''),
                    number_format($compra->total, 2, '.', ''),
                    $compra->nombreProveedor(),
                    $compra->usuario->name ?? 'Sistema',
                ]);
            }

        });
    }

    /**
     * Exporta el historial de compras a PDF.
     */
    public function exportarPDF()
    {
        $compras = Compra::with(['producto', 'usuario', 'proveedorRef'])->latest()->get();
        $totalGeneral = $compras->sum('total');

        $pdf = Pdf::loadView('compras.pdf', compact('compras', 'totalGeneral'));
        return $pdf->download('Historial_Compras_' . now()->format('Y-m-d') . '.pdf');
    }
}