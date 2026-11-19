<?php

namespace Tests\Feature;

use App\Models\Abono;
use App\Models\Categoria;
use App\Models\CierreCaja;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * 1. "Imprimir" abre un PDF (nunca imprime la pantalla) y los tickets también son PDF.
 * 2. Clientes con deuda y sin deuda por separado, en la lista, el CSV y el PDF.
 * 3. Productos, ventas y ganancias filtrados por categoría (frutas, verduras…).
 */
class ReportesFiltrosTest extends TestCase
{
    use RefreshDatabase;

    private User $encargado;
    private Categoria $frutas;
    private Categoria $verduras;
    private Producto $mango;
    private Producto $papa;

    protected function setUp(): void
    {
        parent::setUp();
        CierreCaja::olvidarEstados();
        Http::fake();
        $this->encargado = User::factory()->create(['rol' => 'Encargado']);
        $this->frutas = Categoria::create(['nombre' => 'Frutas']);
        $this->verduras = Categoria::create(['nombre' => 'Verduras']);
        $this->mango = $this->producto('Mango', $this->frutas);
        $this->papa = $this->producto('Papa', $this->verduras);
    }

    private function producto(string $nombre, Categoria $categoria): Producto
    {
        return Producto::create([
            'categoria_id' => $categoria->id, 'nombre' => $nombre, 'unidad_medida' => 'kg',
            'precio' => 2, 'stock' => 50, 'stock_minimo' => 1,
        ]);
    }

    /** Venta con una línea por producto: [producto, cantidad]. */
    private function venta(array $lineas, array $datos = []): Venta
    {
        $total = collect($lineas)->sum(fn ($l) => $l[1] * 2);
        $venta = Venta::create($datos + ['user_id' => $this->encargado->id, 'fecha' => now(), 'total' => $total, 'tipo_pago' => 'contado', 'moneda_pago' => 'USD']);
        foreach ($lineas as [$producto, $cantidad]) {
            $venta->detalles()->create(['producto_id' => $producto->id, 'cantidad' => $cantidad, 'precio_unitario' => 2, 'subtotal' => $cantidad * 2]);
        }

        return $venta;
    }

    private function csv($respuesta): string
    {
        return $respuesta->assertOk()->streamedContent();
    }

    // ---------- Imprimir = PDF ----------

    public function test_imprimir_abre_el_pdf_y_no_la_pantalla(): void
    {
        $html = $this->actingAs($this->encargado)->get(route('productos.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('window.print()', $html);
        $this->assertStringContainsString(e(route('productos.pdf') . '?ver=1'), $html);
    }

    public function test_con_ver_el_pdf_se_abre_en_el_navegador_y_sin_ver_se_descarga(): void
    {
        $this->actingAs($this->encargado)->get(route('productos.pdf', ['ver' => 1]))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('inline', $this->get(route('productos.pdf', ['ver' => 1]))->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('attachment', $this->get(route('productos.pdf'))->headers->get('Content-Disposition'));
    }

    public function test_la_etiqueta_del_producto_es_un_pdf_con_su_codigo_de_barras_y_qr(): void
    {
        $this->mango->update(['codigo_barras' => '7591234567890', 'codigo_qr' => 'MANGO-01']);

        $this->actingAs($this->encargado)->get(route('productos.etiqueta', $this->mango))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');

        // La pantalla del kardex muestra los mismos códigos (SVG del servidor) y ya no imprime la página
        $html = $this->actingAs($this->encargado)->get(route('productos.kardex', $this->mango))->assertOk()->getContent();
        $this->assertStringContainsString(e(route('productos.etiqueta', $this->mango)), $html);
        $this->assertStringContainsString('MANGO-01', $html);
        $this->assertStringNotContainsString('window.print', $html);
        $this->assertStringContainsString(\App\Support\Etiqueta::codigoBarrasSvg($this->mango), $html);
        $this->assertStringContainsString(\App\Support\Etiqueta::qrSvg($this->mango->fresh()), $html);
    }

    public function test_un_codigo_de_barras_que_no_se_puede_dibujar_no_rompe_la_etiqueta(): void
    {
        $this->mango->update(['codigo_barras' => 'ÑÑÑ']);

        $this->assertNull(\App\Support\Etiqueta::codigoBarrasSvg($this->mango));
        $this->actingAs($this->encargado)->get(route('productos.etiqueta', $this->mango))->assertOk();

        // Y el formulario ya no deja guardar un código así
        $this->actingAs($this->encargado)->put(route('productos.update', $this->mango), [
            'nombre' => 'Mango', 'categoria_id' => $this->frutas->id, 'unidad_medida' => 'kg', 'codigo_barras' => 'ÑÑÑ',
            'precio' => 2, 'stock' => 50, 'stock_minimo' => 1, 'activo' => 1,
        ])->assertSessionHasErrors('codigo_barras');
    }

    public function test_el_ticket_de_venta_es_un_pdf(): void
    {
        $venta = $this->venta([[$this->mango, 2]]);

        $this->actingAs($this->encargado)->get(route('ventas.ticket', $venta))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    // ---------- Clientes con y sin deuda ----------

    public function test_clientes_con_deuda_y_sin_deuda_por_separado(): void
    {
        $debe = Cliente::create(['nombre' => 'Ana Debe']);
        $pagoTodo = Cliente::create(['nombre' => 'Beto Pagó']);
        Cliente::create(['nombre' => 'Carla Nueva']);
        $this->venta([[$this->mango, 5]], ['cliente_id' => $debe->id, 'tipo_pago' => 'credito', 'fecha' => now()->subDays(10)]);
        $this->venta([[$this->mango, 2]], ['cliente_id' => $pagoTodo->id, 'tipo_pago' => 'credito']);
        Abono::create(['cliente_id' => $pagoTodo->id, 'user_id' => $this->encargado->id, 'monto' => 4, 'moneda' => 'USD', 'monto_moneda' => 4, 'fecha' => now()]);

        $conDeuda = $this->csv($this->actingAs($this->encargado)->get(route('clientes.excel', ['estado' => 'con_deuda'])));
        $this->assertStringContainsString('Ana Debe', $conDeuda);
        $this->assertStringNotContainsString('Beto Pagó', $conDeuda);
        $this->assertStringNotContainsString('Carla Nueva', $conDeuda);

        $sinDeuda = $this->csv($this->actingAs($this->encargado)->get(route('clientes.excel', ['estado' => 'sin_deuda'])));
        $this->assertStringNotContainsString('Ana Debe', $sinDeuda);
        $this->assertStringContainsString('Beto Pagó', $sinDeuda);
        $this->assertStringContainsString('Carla Nueva', $sinDeuda);

        $this->actingAs($this->encargado)->get(route('clientes.index', ['estado' => 'con_deuda']))
            ->assertOk()
            ->assertSee('Ana Debe')
            ->assertDontSee('Carla Nueva')
            ->assertSee('debe desde ' . now()->subDays(10)->format('d/m/Y'))
            ->assertSee(e(route('clientes.pdf', ['estado' => 'con_deuda'])), false);

        $pdf = $this->actingAs($this->encargado)->get(route('clientes.pdf', ['estado' => 'con_deuda']))->assertOk();
        $this->assertStringContainsString('Clientes_con_deuda_', $pdf->headers->get('Content-Disposition'));
    }

    public function test_desde_cuando_debe_cuenta_los_abonos_sobre_el_fiado_mas_viejo(): void
    {
        $cliente = Cliente::create(['nombre' => 'Ana']);
        $this->venta([[$this->mango, 5]], ['cliente_id' => $cliente->id, 'tipo_pago' => 'credito', 'fecha' => now()->subDays(20)]); // $10
        $this->venta([[$this->mango, 5]], ['cliente_id' => $cliente->id, 'tipo_pago' => 'credito', 'fecha' => now()->subDays(3)]);  // $10
        $this->assertSame(now()->subDays(20)->toDateString(), $cliente->deudaDesde()->toDateString());

        Abono::create(['cliente_id' => $cliente->id, 'user_id' => $this->encargado->id, 'monto' => 10, 'moneda' => 'USD', 'monto_moneda' => 10, 'fecha' => now()]);

        $this->assertSame(now()->subDays(3)->toDateString(), $cliente->deudaDesde()->toDateString());
    }

    // ---------- Por categoría ----------

    public function test_productos_por_categoria_en_la_lista_y_las_exportaciones(): void
    {
        $this->actingAs($this->encargado)->get(route('productos.index', ['categoria_id' => $this->frutas->id]))
            ->assertOk()->assertSee('Mango')->assertDontSee('>Papa<', false);

        $csv = $this->csv($this->actingAs($this->encargado)->get(route('productos.excel', ['categoria_id' => $this->frutas->id])));
        $this->assertStringContainsString('Mango', $csv);
        $this->assertStringNotContainsString('Papa', $csv);

        // Categoría + filtro de alerta: solo las frutas agotadas
        $this->producto('Fresa', $this->frutas)->update(['stock' => 0]);
        $agotadas = $this->csv($this->actingAs($this->encargado)->get(route('productos.excel', ['categoria_id' => $this->frutas->id, 'filtro' => 'agotados'])));
        $this->assertStringContainsString('Fresa', $agotadas);
        $this->assertStringNotContainsString('Mango', $agotadas);

        $pdf = $this->actingAs($this->encargado)->get(route('productos.pdf', ['categoria_id' => $this->frutas->id]))->assertOk();
        $this->assertStringContainsString('Inventario_frutas_', $pdf->headers->get('Content-Disposition'));
    }

    public function test_ventas_por_categoria_suman_solo_lo_de_esa_categoria(): void
    {
        $mixta = $this->venta([[$this->mango, 3], [$this->papa, 1]]);   // $6 fruta + $2 verdura
        $soloPapa = $this->venta([[$this->papa, 4]]);

        $respuesta = $this->actingAs($this->encargado)->get(route('ventas.index', ['categoria_id' => $this->frutas->id]))->assertOk();
        $this->assertEquals([$mixta->id], $respuesta->viewData('ventas')->pluck('id')->all());
        $this->assertEquals(6, $respuesta->viewData('totalCategoria'));
        $respuesta->assertSee('Vendido en Frutas');

        $csv = $this->csv($this->actingAs($this->encargado)->get(route('ventas.excel', ['categoria_id' => $this->verduras->id])));
        $this->assertStringContainsString("\n{$soloPapa->id},", $csv);
        $this->assertStringContainsString("\n{$mixta->id},", $csv);

        $this->actingAs($this->encargado)->get(route('ventas.listado.pdf', ['categoria_id' => $this->frutas->id]))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_las_fechas_del_filtro_se_validan(): void
    {
        $this->actingAs($this->encargado)->get(route('ventas.index', ['fecha_inicio' => '2026-11-10', 'fecha_fin' => '2026-11-01']))
            ->assertSessionHasErrors('fecha_fin');
        $this->actingAs($this->encargado)->get(route('ventas.index', ['categoria_id' => 999]))
            ->assertSessionHasErrors('categoria_id');
    }

    public function test_ganancias_por_categoria(): void
    {
        Compra::create(['producto_id' => $this->mango->id, 'user_id' => $this->encargado->id, 'cantidad' => 10, 'precio_compra' => 1, 'total' => 10, 'fecha' => now()]);
        $this->venta([[$this->mango, 3], [$this->papa, 1]]);

        $csv = $this->csv($this->actingAs($this->encargado)->get(route('reportes.ganancias.excel', ['categoria_id' => $this->frutas->id])));

        $this->assertStringContainsString('Mango', $csv);
        $this->assertStringNotContainsString('Papa', $csv);

        $this->actingAs($this->encargado)->get(route('reportes.ganancias.pdf', ['categoria_id' => $this->frutas->id]))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }
}
