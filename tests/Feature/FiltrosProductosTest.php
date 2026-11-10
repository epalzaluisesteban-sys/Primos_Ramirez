<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\CierreCaja;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Services\AlertasInventarioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Las alertas de stock ya no son una pantalla aparte: son filtros de Productos,
 * y la campana enlaza directo a cada filtro.
 */
class FiltrosProductosTest extends TestCase
{
    use RefreshDatabase;

    private User $encargado;

    protected function setUp(): void
    {
        parent::setUp();
        CierreCaja::olvidarEstados();
        Http::fake();
        $this->encargado = User::factory()->create(['rol' => 'Encargado']);
    }

    private function producto(string $nombre, array $datos = []): Producto
    {
        return Producto::create($datos + [
            'categoria_id' => Categoria::firstOrCreate(['nombre' => 'Frutas'])->id,
            'nombre' => $nombre,
            'unidad_medida' => 'kg',
            'precio' => 2,
            'stock' => 50,
            'stock_minimo' => 5,
        ]);
    }

    private function nombresEnFiltro(string $filtro): array
    {
        $productos = $this->actingAs($this->encargado)
            ->get(route('productos.index', ['filtro' => $filtro]))
            ->assertOk()
            ->viewData('productos');

        return $productos->pluck('nombre')->sort()->values()->all();
    }

    private function catalogo(): void
    {
        $this->producto('Normal');
        $this->producto('Agotado', ['stock' => 0]);
        $this->producto('Poco', ['stock' => 3]);
        $this->producto('Vencido', ['fecha_vencimiento' => now()->subDays(2)]);
        $this->producto('PorVencer', ['fecha_vencimiento' => now()->addDays(3)]);
        $this->producto('Lejano', ['fecha_vencimiento' => now()->addDays(30)]);
        $this->producto('Apagado', ['stock' => 0, 'activo' => false]);

        // "Rapido" tiene stock de sobra según el mínimo, pero se venden ~10 por semana y quedan 8
        $rapido = $this->producto('Rapido', ['stock' => 8, 'stock_minimo' => 1]);
        $venta = Venta::create(['user_id' => $this->encargado->id, 'fecha' => now()->subDays(3), 'tipo_pago' => 'contado', 'moneda_pago' => 'USD', 'total' => 80]);
        DetalleVenta::create(['venta_id' => $venta->id, 'producto_id' => $rapido->id, 'cantidad' => 40, 'precio_unitario' => 2, 'subtotal' => 80]);
    }

    public function test_cada_filtro_muestra_sus_productos(): void
    {
        $this->catalogo();

        $this->assertSame(['Agotado'], $this->nombresEnFiltro('agotados'));
        $this->assertSame(['Poco'], $this->nombresEnFiltro('stock_bajo'));
        $this->assertSame(['PorVencer', 'Vencido'], $this->nombresEnFiltro('vencimiento'));
        $this->assertSame(['Agotado', 'Poco', 'Rapido'], $this->nombresEnFiltro('comprar'));
        $this->assertSame(['Apagado'], $this->nombresEnFiltro('inactivos'));
        $this->assertSame(['Agotado', 'Poco', 'PorVencer', 'Rapido', 'Vencido'], $this->nombresEnFiltro('alertas'));
        $this->assertCount(8, $this->nombresEnFiltro('todos'));
    }

    public function test_un_filtro_desconocido_muestra_todos(): void
    {
        $this->catalogo();
        $this->assertCount(8, $this->nombresEnFiltro('cualquiera'));
    }

    public function test_los_botones_de_filtro_muestran_cuantos_hay_y_el_boton_comprar_aparece(): void
    {
        $this->catalogo();
        $agotado = Producto::where('nombre', 'Agotado')->first();
        $vencido = Producto::where('nombre', 'Vencido')->first();

        $respuesta = $this->actingAs($this->encargado)->get(route('productos.index', ['filtro' => 'alertas']))->assertOk();

        $this->assertSame(1, $respuesta->viewData('conteos')['agotados']);
        $this->assertSame(5, $respuesta->viewData('conteos')['alertas']);
        $respuesta->assertSee('Conviene comprar')
            ->assertSee(route('productos.index', ['filtro' => 'stock_bajo']), false)
            ->assertSee(route('compras.index', ['producto' => $agotado->id]), false)
            ->assertSee(route('mermas.index', ['producto' => $vencido->id]), false);
    }

    public function test_filtro_vacio_lo_dice(): void
    {
        $this->producto('Normal');

        $this->actingAs($this->encargado)->get(route('productos.index', ['filtro' => 'agotados']))
            ->assertOk()
            ->assertSee('Nada en "Agotados" por ahora.', false);
    }

    public function test_el_menu_ya_no_tiene_alertas_de_stock_y_el_enlace_viejo_lleva_al_filtro(): void
    {
        $this->actingAs($this->encargado)->get(route('productos.index'))
            ->assertOk()
            ->assertDontSee('Alertas de stock');

        $this->actingAs($this->encargado)->get('/inventario/alertas')
            ->assertRedirect(route('productos.index', ['filtro' => 'alertas']));
    }

    public function test_la_campana_lleva_a_cada_filtro(): void
    {
        $this->producto('Agotado', ['stock' => 0]);
        $this->producto('Poco', ['stock' => 3]);
        $this->producto('PorVencer', ['fecha_vencimiento' => now()->addDays(2)]);
        $this->producto('Apagado', ['stock' => 0, 'activo' => false, 'fecha_vencimiento' => now()->subDay()]);

        $html = $this->actingAs($this->encargado)->get(route('productos.index'))->assertOk()->getContent();

        $this->assertStringContainsString('href="' . route('productos.index', ['filtro' => 'agotados']) . '"', $html);
        $this->assertStringContainsString('href="' . route('productos.index', ['filtro' => 'stock_bajo']) . '"', $html);
        $this->assertStringContainsString('href="' . route('productos.index', ['filtro' => 'vencimiento']) . '"', $html);
        $this->assertStringContainsString('Ver productos con alerta', $html);
        $this->assertStringNotContainsString('Apagado vencido', $html); // los inactivos no avisan
    }

    public function test_al_obrero_la_campana_le_avisa_sin_enlace_a_productos(): void
    {
        $obrero = User::factory()->create(['rol' => 'Obrero']);
        $this->producto('Agotado', ['stock' => 0]);

        $html = $this->actingAs($obrero)->get(route('caja.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Producto agotado: Agotado', $html);
        $this->assertStringNotContainsString(route('productos.index', ['filtro' => 'agotados']), $html);
        $this->assertStringNotContainsString('Ver productos con alerta', $html);
    }

    public function test_filtro_valido(): void
    {
        $this->assertSame('agotados', AlertasInventarioService::filtroValido('agotados'));
        $this->assertSame('todos', AlertasInventarioService::filtroValido('<script>'));
        $this->assertSame('todos', AlertasInventarioService::filtroValido(null));
    }
}
