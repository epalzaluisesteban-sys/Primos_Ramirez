<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\Producto;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompraController extends Controller
{
    public function index()
    {
        $compras = Compra::with(['producto', 'usuario', 'proveedorRef'])->latest()->get();
        $productos = Producto::activos()->orderBy('nombre')->get();
        $proveedores = Proveedor::where('activo', true)->with('categorias')->orderBy('nombre')->get();

        return view('compras.index', compact('compras', 'productos', 'proveedores'));
    }

    public function show(Request $request, Compra $compra)
    {
        $compra->load(['producto', 'usuario', 'proveedorRef']);

        return view($request->ajax() ? 'compras._ficha' : 'compras.show', compact('compra'));
    }

    public function store(Request $request)
    {
        $request->merge(['tipo_pago' => $request->input('tipo_pago') ?: 'contado']);
        $request->validate([
            'producto_id' => ['required', Rule::exists('productos', 'id')->where('activo', true)],
            'cantidad' => 'required|numeric|min:0.001|max:9999999',
            'precio_compra' => 'required|numeric|min:0.01|max:99999999',
            'proveedor_id' => ['nullable', 'required_if:tipo_pago,credito', Rule::exists('proveedores', 'id')->where('activo', true)],
            'proveedor' => 'nullable|string|max:255',
            'tipo_pago' => 'required|in:contado,credito',
        ], [
            'proveedor_id.required_if' => 'Una compra a crédito necesita el proveedor registrado, para saber a quién se le debe.',
            'proveedor_id.exists' => 'Ese proveedor no está registrado o está desactivado.',
        ]);

        // Productos por unidad (unidad, caja, atado, saco) se reciben en cantidades enteras
        if ($error = Producto::findOrFail($request->producto_id)->errorCantidad($request->cantidad)) {
            return back()->withErrors(['cantidad' => $error])->withInput();
        }

        $proveedor = $request->proveedor_id ? Proveedor::find($request->proveedor_id) : null;
        $credito = $request->tipo_pago === 'credito';
        if ($credito && !$proveedor->da_credito) {
            return back()->withErrors(['tipo_pago' => "{$proveedor->nombre} no tiene marcado que da crédito. Regístrala de contado, o marca el crédito en la ficha del proveedor (Editar)."])->withInput();
        }

        DB::transaction(function () use ($request, $proveedor, $credito) {
            $producto = Producto::lockForUpdate()->findOrFail($request->producto_id);

            Compra::create([
                'producto_id' => $producto->id,
                'user_id' => auth()->id(),
                'proveedor_id' => $proveedor?->id,
                'cantidad' => $request->cantidad,
                'precio_compra' => $request->precio_compra,
                'total' => round($request->cantidad * $request->precio_compra, 2),
                'tipo_pago' => $request->tipo_pago,
                'vence_el' => $credito && $proveedor->dias_credito ? today()->addDays($proveedor->dias_credito) : null,
                'proveedor' => $proveedor->nombre ?? ($request->proveedor ?: 'Proveedor General'),
                'fecha' => now(),
            ]);

            $producto->increment('stock', $request->cantidad);
        });

        return redirect()->route('compras.index')->with('success', $credito
            ? 'Mercancía recibida a crédito. El stock subió y la deuda quedó anotada en la ficha del proveedor.'
            : 'Entrada de mercancía registrada. Stock actualizado correctamente.');
    }

    /**
     * Exporta el historial de compras a un archivo CSV compatible con Excel.
     */
    public function exportarExcel(): StreamedResponse
    {
        $compras = Compra::with(['producto', 'usuario', 'proveedorRef'])->latest()->get();
        return $this->descargarCsv('compras_' . now()->format('Y-m-d_His') . '.csv', function (callable $fila) use ($compras) {
            $fila(['Fecha', 'Producto', 'Cantidad', 'Costo Unitario', 'Total', 'Pago', 'Vence', 'Proveedor', 'Registrado Por']);

            foreach ($compras as $compra) {
                $fila([
                    $compra->fecha,
                    $compra->producto->nombre ?? 'Eliminado',
                    $compra->cantidad,
                    number_format($compra->precio_compra, 2, '.', ''),
                    number_format($compra->total, 2, '.', ''),
                    $compra->tipo_pago === 'credito' ? 'Crédito' : 'Contado',
                    $compra->vence_el?->format('d/m/Y'),
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
        return $this->entregarPdf($pdf, 'Historial_Compras_' . now()->format('Y-m-d') . '.pdf');
    }
}