<?php

namespace Tests\Feature;

use App\Models\AjusteInventario;
use App\Models\Categoria;
use App\Models\CierreCaja;
use App\Models\Compra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Sección Inventario: desactivar proveedores con compras, botones de borrar que solo
 * aparecen cuando el borrado es posible, kardex legible y compra preseleccionada desde alertas.
 */
class InventarioPantallasTest extends TestCase
{
    use RefreshDatabase;

    private User $dueno;

    protected function setUp(): void
    {
        parent::setUp();
        CierreCaja::olvidarEstados();
        Http::fake();
        $this->dueno = User::factory()->create(['rol' => 'Dueno']);
    }

    private function producto(array $datos = []): Producto
    {
        $categoria = Categoria::firstOrCreate(['nombre' => 'Frutas']);

        return Producto::create($datos + [
            'categoria_id' => $categoria->id,
            'nombre' => 'Cambur',
            'unidad_medida' => 'kg',
            'precio' => 2,
            'stock' => 0,
            'stock_minimo' => 1,
        ]);
    }

    private function accion(string $ruta, $modelo): string
    {
        return 'action="' . route($ruta, $modelo) . '"';
    }

    // ---------- Proveedores ----------

    public function test_el_encargado_desactiva_y_reactiva_un_proveedor_sin_perder_sus_compras(): void
    {
        $encargado = User::factory()->create(['rol' => 'Encargado']);
        $proveedor = Proveedor::create(['nombre' => 'Mercado Mayorista', 'activo' => true]);
        Compra::create([
            'producto_id' => $this->producto()->id,
            'proveedor_id' => $proveedor->id,
            'user_id' => $encargado->id,
            'cantidad' => 5,
            'precio_compra' => 1,
            'total' => 5,
            'fecha' => now(),
        ]);

        $this->actingAs($encargado)->patch(route('proveedores.activo', $proveedor))->assertSessionHasNoErrors();
        $this->assertFalse($proveedor->fresh()->activo);

        $this->actingAs($encargado)->patch(route('proveedores.activo', $proveedor))->assertSessionHasNoErrors();
        $this->assertTrue($proveedor->fresh()->activo);

        $this->assertSame(1, $proveedor->compras()->count());
    }

    public function test_la_tabla_de_proveedores_ofrece_desactivar_o_eliminar_segun_sus_compras(): void
    {
        $conCompras = Proveedor::create(['nombre' => 'Con compras', 'activo' => true]);
        $sinCompras = Proveedor::create(['nombre' => 'Sin compras', 'activo' => true]);
        Compra::create([
            'producto_id' => $this->producto()->id,
            'proveedor_id' => $conCompras->id,
            'user_id' => $this->dueno->id,
            'cantidad' => 1,
            'precio_compra' => 1,
            'total' => 1,
            'fecha' => now(),
        ]);

        $this->actingAs($this->dueno)->get(route('proveedores.index'))
            ->assertOk()
            ->assertSee($this->accion('proveedores.activo', $conCompras), false)
            ->assertDontSee($this->accion('proveedores.destroy', $conCompras), false)
            ->assertSee($this->accion('proveedores.destroy', $sinCompras), false)
            ->assertDontSee($this->accion('proveedores.activo', $sinCompras), false);
    }

    public function test_crear_proveedor_ignora_campos_no_permitidos(): void
    {
        $this->actingAs($this->dueno)
            ->post(route('proveedores.store'), ['nombre' => 'Nuevo', 'activo' => 0])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Proveedor::where('nombre', 'Nuevo')->first()->activo);
    }

    // ---------- Categorías ----------

    public function test_solo_las_categorias_vacias_muestran_la_papelera(): void
    {
        $conProductos = Categoria::create(['nombre' => 'Frutas']);
        $vacia = Categoria::create(['nombre' => 'Granos']);
        $this->producto(['categoria_id' => $conProductos->id]);

        $this->actingAs($this->dueno)->get(route('categorias.index'))
            ->assertOk()
            ->assertSee($this->accion('categorias.destroy', $vacia), false)
            ->assertDontSee($this->accion('categorias.destroy', $conProductos), false);
    }

    public function test_el_encargado_no_ve_la_papelera_de_categorias(): void
    {
        $encargado = User::factory()->create(['rol' => 'Encargado']);
        $vacia = Categoria::create(['nombre' => 'Granos']);

        $this->actingAs($encargado)->get(route('categorias.index'))
            ->assertOk()
            ->assertDontSee($this->accion('categorias.destroy', $vacia), false);
    }

    // ---------- Kardex y alertas ----------

    public function test_el_kardex_muestra_entradas_y_salidas_en_una_columna(): void
    {
        $producto = $this->producto();
        AjusteInventario::registrar($producto, 10, 'Inventario inicial', $this->dueno->id);
        AjusteInventario::registrar($producto->fresh(), 7, 'Conteo físico', $this->dueno->id);

        $this->actingAs($this->dueno)->get(route('productos.kardex', $producto))
            ->assertOk()
            ->assertSee('Movimiento')
            ->assertSee('+10')
            ->assertSee('−3')
            ->assertSee('Los movimientos cuadran con el stock actual.');
    }

    public function test_las_alertas_llevan_a_compras_con_el_producto_elegido(): void
    {
        $agotado = $this->producto(['stock' => 0]);

        // Las alertas ahora son el filtro "Agotados" de Productos, con su botón "Comprar"
        $this->actingAs($this->dueno)->get(route('productos.index', ['filtro' => 'agotados']))
            ->assertOk()
            ->assertSee(route('compras.index', ['producto' => $agotado->id]), false);

        $html = $this->actingAs($this->dueno)->get(route('compras.index', ['producto' => $agotado->id]))
            ->assertOk()
            ->getContent();
        $this->assertMatchesRegularExpression('/<option value="' . $agotado->id . '"[^>]* selected/', $html);
    }
}
