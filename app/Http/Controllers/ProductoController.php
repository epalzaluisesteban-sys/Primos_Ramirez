<?php

namespace App\Http\Controllers;

use App\Support\Almacen;
use App\Models\AjusteInventario;
use App\Models\Producto;
use App\Models\Categoria;
use App\Services\AlertasInventarioService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductoController extends Controller
{
    /**
     * Listado con filtros (?filtro=agotados, stock_bajo, vencimiento, comprar, alertas, inactivos).
     * Aquí viven las alertas de stock; la campana enlaza directo a cada filtro.
     */
    public function index(Request $request, AlertasInventarioService $alertas)
    {
        $filtro = AlertasInventarioService::filtroValido($request->query('filtro'));
        $grupos = $alertas->clasificar(Producto::with('categoria')->latest()->get());

        $productos = $grupos[$filtro];
        $conteos = array_map(fn ($g) => $g->count(), $grupos);
        // Para el botón "Comprar" en cada fila
        $idsComprar = $grupos['comprar']->pluck('id')->flip();

        return view('productos.index', compact('productos', 'filtro', 'conteos', 'idsComprar'));
    }

    public function create()
    {
        $categorias = Categoria::all();
        return view('productos.create', compact('categorias'));
    }

    private function validacionProducto(): array
    {
        return [
            'nombre' => 'required|string|max:255',
            'categoria_id' => 'required|exists:categorias,id',
            'foto' => 'nullable|image|max:2048',
            'marca' => 'nullable|string|max:255',
            'ubicacion' => 'nullable|string|max:255',
            'codigo_barras' => 'nullable|string|max:100',
            'codigo_qr' => 'nullable|string|max:255',
            'unidad_medida' => 'required|in:' . implode(',', array_keys(Producto::UNIDADES)),
            'precio' => 'required|numeric|min:0',
            'precio_compra' => 'nullable|numeric|min:0',
            'stock' => ['required', 'numeric', 'min:0', $this->sinDecimalesSiEsPorUnidad()],
            'stock_minimo' => ['required', 'numeric', 'min:0', $this->sinDecimalesSiEsPorUnidad()],
            'stock_maximo' => ['nullable', 'numeric', 'min:0', 'gte:stock_minimo', $this->sinDecimalesSiEsPorUnidad()],
            'fecha_vencimiento' => 'nullable|date',
        ];
    }

    // Productos por unidad, caja, atado o saco: las existencias van sin decimales.
    private function sinDecimalesSiEsPorUnidad(): \Closure
    {
        return function (string $atributo, $valor, \Closure $falla) {
            if (Producto::unidadEsEntera(request('unidad_medida')) && is_numeric($valor) && !Producto::esEntero($valor)) {
                $falla('La :attribute va sin decimales porque el producto se cuenta por ' . mb_strtolower(Producto::UNIDADES[request('unidad_medida')]) . '.');
            }
        };
    }

    // Campos que se guardan directo desde el formulario. El stock NO está aquí:
    // cualquier cambio de existencia pasa por AjusteInventario para que quede en el kardex.
    private const CAMPOS = [
        'categoria_id', 'nombre', 'marca', 'ubicacion', 'codigo_barras', 'codigo_qr', 'unidad_medida',
        'precio', 'precio_compra', 'stock_minimo', 'stock_maximo', 'fecha_vencimiento',
    ];

    public function store(Request $request)
    {
        $request->validate($this->validacionProducto());

        $datos = $request->only(self::CAMPOS) + ['stock' => 0, 'activo' => true];
        if ($request->hasFile('foto')) {
            $datos['foto'] = Almacen::guardar($request->file('foto'), 'productos');
        }

        DB::transaction(function () use ($datos, $request) {
            $producto = Producto::create($datos);
            AjusteInventario::registrar($producto, (float) $request->stock, 'Inventario inicial', auth()->id());
        });

        return redirect()->route('productos.index')->with('success', 'Producto creado exitosamente.');
    }

    public function edit(Producto $producto)
    {
        $categorias = Categoria::all();
        return view('productos.edit', compact('producto', 'categorias'));
    }

    public function update(Request $request, Producto $producto)
    {
        $request->validate($this->validacionProducto() + [
            'motivo_ajuste' => 'nullable|string|max:255',
            'activo' => 'nullable|boolean',
        ]);

        $datos = $request->only(self::CAMPOS) + ['activo' => $request->boolean('activo')];
        if ($request->hasFile('foto')) {
            if ($producto->foto) {
                Almacen::borrar($producto->foto);
            }
            $datos['foto'] = Almacen::guardar($request->file('foto'), 'productos');
        }

        DB::transaction(function () use ($producto, $datos, $request) {
            // Se relee con bloqueo: si hubo una venta mientras se editaba, el ajuste se calcula
            // contra el stock real del momento y el kardex sigue cuadrando.
            $actual = Producto::lockForUpdate()->findOrFail($producto->id);
            $actual->update($datos);
            AjusteInventario::registrar(
                $actual,
                (float) $request->stock,
                $request->filled('motivo_ajuste') ? $request->motivo_ajuste : 'Corrección manual de existencia',
                auth()->id()
            );
        });

        return redirect()->route('productos.index')->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Producto $producto)
    {
        if ($producto->tieneHistorial()) {
            return back()->withErrors(['error' => "No se puede eliminar {$producto->nombre}: tiene compras, ventas o pérdidas registradas. Desactívalo desde Editar para que no aparezca en ventas ni compras."]);
        }

        if ($producto->foto) {
            Almacen::borrar($producto->foto);
        }

        $producto->delete();
        return redirect()->route('productos.index')->with('success', 'Producto eliminado.');
    }

    // Exporta el catálogo/inventario (stock, precio, valor) a CSV compatible con Excel.
    public function exportarExcel(): StreamedResponse
    {
        $productos = Producto::with('categoria')->orderBy('nombre')->get();
        return $this->descargarCsv('inventario_productos_' . now()->format('Y-m-d_His') . '.csv', function (callable $fila) use ($productos) {
            $fila(['Producto', 'Marca', 'Categoría', 'Ubicación', 'Código de Barras', 'Unidad', 'Precio Compra', 'Precio Venta', '% Ganancia', 'Stock', 'Stock Mínimo', 'Stock Máximo', 'Estado', 'Vencimiento', 'Valor Inventario']);

            foreach ($productos as $p) {
                $fila([
                    $p->nombre,
                    $p->marca,
                    $p->categoria->nombre ?? 'Sin categoría',
                    $p->ubicacion,
                    $p->codigo_barras,
                    $p->unidadLabel(),
                    $p->precio_compra !== null ? number_format($p->precio_compra, 2, '.', '') : '',
                    number_format($p->precio, 2, '.', ''),
                    $p->porcentajeGanancia() !== null ? number_format($p->porcentajeGanancia(), 1, '.', '') : '',
                    $p->stock,
                    $p->stock_minimo,
                    $p->stock_maximo,
                    $this->estadoProducto($p),
                    $p->fecha_vencimiento ? $p->fecha_vencimiento->format('Y-m-d') : '',
                    number_format($p->stock * $p->precio, 2, '.', ''),
                ]);
            }

        });
    }

    // Exporta el catálogo/inventario a PDF.
    public function exportarPDF()
    {
        $productos = Producto::with('categoria')->orderBy('nombre')->get();
        $valorTotalInventario = $productos->sum(fn ($p) => $p->stock * $p->precio);
        $estados = $productos->mapWithKeys(fn ($p) => [$p->id => $this->estadoProducto($p)]);

        $pdf = Pdf::loadView('productos.pdf', compact('productos', 'valorTotalInventario', 'estados'));
        return $pdf->download('Inventario_Productos_' . now()->format('Y-m-d') . '.pdf');
    }

    private function estadoProducto(Producto $producto): string
    {
        if ($producto->stock <= 0) {
            return 'Agotado';
        }
        if ($producto->tieneStockCritico()) {
            return 'Crítico';
        }
        return 'Normal';
    }

    public function kardex(Producto $producto)
    {
        $datos = $this->construirMovimientos($producto);

        return view('productos.kardex', array_merge(['producto' => $producto], $datos));
    }

    public function kardexExcel(Producto $producto): StreamedResponse
    {
        $datos = $this->construirMovimientos($producto);
        return $this->descargarCsv('kardex_' . str_replace(' ', '_', $producto->nombre) . '_' . now()->format('Y-m-d_His') . '.csv', function (callable $fila) use ($datos) {
            $fila(['Fecha', 'Hora', 'Entrada', 'Salida', 'Costo Anterior', 'Costo Nuevo', 'Existencia', 'Usuario', 'Observaciones']);

            foreach ($datos['movimientos'] as $mov) {
                $fila([
                    $mov['fecha']->format('Y-m-d'),
                    $mov['fecha']->format('h:i A'),
                    $mov['es_entrada'] ? $mov['cantidad'] : '',
                    !$mov['es_entrada'] ? $mov['cantidad'] : '',
                    $mov['costo_anterior'] !== null ? number_format($mov['costo_anterior'], 2, '.', '') : 'N/D',
                    $mov['costo_nuevo'] !== null ? number_format($mov['costo_nuevo'], 2, '.', '') : 'N/D',
                    $mov['existencia'],
                    $mov['responsable'],
                    $mov['observaciones'],
                ]);
            }

        });
    }

    public function kardexPDF(Producto $producto)
    {
        $datos = $this->construirMovimientos($producto);

        $pdf = Pdf::loadView('productos.kardex-pdf', array_merge(['producto' => $producto], $datos));
        return $pdf->download('Kardex_' . str_replace(' ', '_', $producto->nombre) . '_' . now()->format('Y-m-d') . '.pdf');
    }

    // Construye el historial de movimientos (entradas/salidas), costo promedio ponderado
    // y existencia corrida de un producto. Reutilizado por la vista y ambas exportaciones.
    private function construirMovimientos(Producto $producto): array
    {
        // 1. Entradas (Compras)
        $entradas = $producto->compras()->with('usuario')->orderBy('fecha')->get()->map(function ($c) {
            return [
                'fecha' => Carbon::parse($c->fecha),
                'es_entrada' => true,
                'origen' => 'Compra',
                'cantidad' => (float) $c->cantidad,
                'precio_compra' => (float) $c->precio_compra,
                'responsable' => $c->usuario->name ?? 'Sistema',
                'observaciones' => 'Proveedor: ' . $c->nombreProveedor() . ' — $' . number_format($c->precio_compra, 2) . '/u',
            ];
        });

        // 2. Salidas por Venta
        $salidas = $producto->detallesVenta()->whereHas('venta', fn ($q) => $q->where('anulada', false))->with('venta.usuario', 'venta.cliente')->get()->map(function ($d) {
            $v = $d->venta;
            $obs = $v
                ? ('Venta #' . $v->id . ' — ' . ($v->tipo_pago === 'credito' ? 'Fiado' . ($v->cliente ? " ({$v->cliente->nombre})" : '') : 'Contado') . ' — $' . number_format($d->precio_unitario, 2) . '/u')
                : 'Venta eliminada';
            if ($v && $v->anulada) {
                $obs .= ' — ANULADA' . ($v->motivo_anulacion ? ": {$v->motivo_anulacion}" : '');
            }

            return [
                'fecha' => $v->fecha ?? $d->created_at,
                'es_entrada' => false,
                'origen' => 'Venta #' . $d->venta_id,
                'cantidad' => (float) $d->cantidad,
                'responsable' => $v->usuario->name ?? 'Sistema',
                'observaciones' => $obs,
            ];
        });

        // 3. Salidas por Merma
        $mermas = $producto->mermas()->with('usuario')->orderBy('fecha')->get()->map(function ($m) {
            return [
                'fecha' => $m->fecha,
                'es_entrada' => false,
                'origen' => 'Pérdida',
                'cantidad' => (float) $m->cantidad,
                'responsable' => $m->usuario->name ?? 'Sistema',
                'observaciones' => $m->motivoLabel() . ($m->observacion ? " — {$m->observacion}" : ''),
            ];
        });

        // 4. Ajustes manuales (stock inicial, conteos físicos, correcciones)
        $ajustes = $producto->ajustes()->with('usuario')->orderBy('fecha')->get()->map(function ($a) {
            return [
                'fecha' => $a->fecha,
                'es_entrada' => $a->cantidad > 0,
                'origen' => 'Ajuste',
                'cantidad' => abs($a->cantidad),
                'responsable' => $a->usuario->name ?? 'Sistema',
                'observaciones' => 'Ajuste de inventario' . ($a->motivo ? " — {$a->motivo}" : ''),
            ];
        });

        // 5. Unir todos los movimientos y ordenar cronológicamente (más antiguo primero)
        //    para poder calcular costo promedio ponderado y existencia corrida.
        $movimientos = $entradas->concat($salidas)->concat($mermas)->concat($ajustes)->sortBy('fecha')->values();

        // Costo promedio ponderado: se recalcula únicamente con las Compras (misma
        // metodología que ReporteController), ya que las salidas no alteran el costo.
        $qtyCompras = 0;
        $valorCompras = 0;
        $costoActual = null;
        $existenciaCorrida = 0;

        $movimientos = $movimientos->map(function ($mov) use (&$qtyCompras, &$valorCompras, &$costoActual, &$existenciaCorrida) {
            $mov['costo_anterior'] = $costoActual;

            if ($mov['es_entrada']) {
                // Solo las compras traen costo; un ajuste de entrada no cambia el costo promedio
                if (isset($mov['precio_compra'])) {
                    $qtyCompras += $mov['cantidad'];
                    $valorCompras += $mov['cantidad'] * $mov['precio_compra'];
                    $costoActual = $qtyCompras > 0 ? $valorCompras / $qtyCompras : null;
                }
                $existenciaCorrida += $mov['cantidad'];
            } else {
                $existenciaCorrida -= $mov['cantidad'];
            }

            $mov['costo_nuevo'] = $costoActual;
            $mov['existencia'] = $existenciaCorrida;

            return $mov;
        });

        // La existencia corrida arranca en 0 y refleja todos los movimientos registrados
        // (incluidos los ajustes); si el stock actual difiere, algo lo cambió por fuera del sistema.
        $existenciaFinal = $existenciaCorrida;
        $discrepanciaStock = abs($existenciaFinal - (float) $producto->stock) > 0.0005;

        // 5. Mostrar del más reciente al más antiguo
        $movimientos = $movimientos->sortByDesc('fecha')->values();

        return compact('movimientos', 'existenciaFinal', 'discrepanciaStock');
    }
}
