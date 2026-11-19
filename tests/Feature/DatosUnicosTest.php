<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\CierreCaja;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use App\Support\Unicidad;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Nada se registra dos veces: productos (nombre + marca por unidad, código de barras), categorías,
 * proveedores (nombre y RIF) y clientes (cédula/RIF). Mayúsculas, tildes y espacios no cuentan, y los
 * desactivados también ocupan su nombre o documento.
 */
class DatosUnicosTest extends TestCase
{
    use RefreshDatabase;

    private User $encargado;
    private Categoria $frutas;

    protected function setUp(): void
    {
        parent::setUp();
        CierreCaja::olvidarEstados();
        Http::fake();
        $this->encargado = User::factory()->create(['rol' => 'Encargado']);
        $this->frutas = Categoria::create(['nombre' => 'Frutas']);
    }

    private function crearProducto(array $datos = [])
    {
        return $this->actingAs($this->encargado)->post(route('productos.store'), $datos + [
            'nombre' => 'Mora', 'categoria_id' => $this->frutas->id, 'unidad_medida' => 'kg',
            'precio' => 3, 'stock' => 0, 'stock_minimo' => 1,
        ]);
    }

    // ---------- Productos ----------

    public function test_no_se_registra_el_mismo_producto_aunque_cambien_mayusculas_tildes_o_espacios(): void
    {
        $this->crearProducto()->assertSessionHasNoErrors();

        $this->crearProducto(['nombre' => '  MORÁ  '])
            ->assertSessionHasErrors(['nombre' => 'Ya existe el producto «Mora» por Kilogramo (Kg).']);

        $this->assertSame(1, Producto::count());
    }

    public function test_el_mismo_nombre_si_se_puede_en_otra_unidad_o_con_otra_marca(): void
    {
        $this->crearProducto()->assertSessionHasNoErrors();
        $this->crearProducto(['unidad_medida' => 'caja'])->assertSessionHasNoErrors();
        $this->crearProducto(['marca' => 'Andina'])->assertSessionHasNoErrors();

        $this->assertSame(3, Producto::count());
    }

    public function test_un_producto_desactivado_tambien_ocupa_su_nombre(): void
    {
        $this->crearProducto();
        Producto::first()->update(['activo' => false]);

        $this->crearProducto()->assertSessionHasErrors('nombre');
        $this->assertStringContainsString('Está desactivado: actívalo', session('errors')->first('nombre'));
    }

    public function test_editar_un_producto_sin_cambiar_el_nombre_no_choca_consigo_mismo(): void
    {
        $this->crearProducto(['codigo_barras' => '7591234567890']);
        $producto = Producto::first();

        $this->actingAs($this->encargado)->put(route('productos.update', $producto), [
            'nombre' => 'mora', 'categoria_id' => $this->frutas->id, 'unidad_medida' => 'kg', 'codigo_barras' => '7591234567890',
            'precio' => 4, 'stock' => 0, 'stock_minimo' => 1, 'activo' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertEquals(4, $producto->fresh()->precio);
    }

    public function test_el_codigo_de_barras_no_se_repite(): void
    {
        $this->crearProducto(['codigo_barras' => '759 1234 567890'])->assertSessionHasNoErrors();
        $this->assertSame('7591234567890', Producto::first()->codigo_barras);

        $this->crearProducto(['nombre' => 'Fresa', 'codigo_barras' => '7591234567890'])
            ->assertSessionHasErrors(['codigo_barras' => 'Ese código de barras ya es del producto «Mora».']);
    }

    public function test_la_base_de_datos_rechaza_el_producto_repetido_aunque_no_pase_por_el_formulario(): void
    {
        $datos = ['categoria_id' => $this->frutas->id, 'unidad_medida' => 'kg', 'precio' => 1, 'stock' => 0, 'stock_minimo' => 1];
        Producto::create(['nombre' => 'Mora'] + $datos);

        $this->expectException(UniqueConstraintViolationException::class);
        Producto::create(['nombre' => 'mora '] + $datos);
    }

    // ---------- Categorías ----------

    public function test_no_se_repite_la_categoria(): void
    {
        $this->actingAs($this->encargado)->post(route('categorias.store'), ['nombre' => 'FRUTÁS'])
            ->assertSessionHasErrors(['nombre' => 'Ya existe la categoría «Frutas».']);

        $this->actingAs($this->encargado)->put(route('categorias.update', $this->frutas), ['nombre' => 'frutas'])
            ->assertSessionHasNoErrors();
        $this->assertSame(1, Categoria::count());
    }

    // ---------- Proveedores ----------

    public function test_no_se_repite_el_nombre_ni_el_rif_del_proveedor(): void
    {
        $guardar = fn (array $datos) => $this->actingAs($this->encargado)->post(route('proveedores.store'), $datos);

        $guardar(['nombre' => 'Agro Andes C.A.', 'documento_tipo' => 'J', 'documento_numero' => '12345678-9'])->assertSessionHasNoErrors();
        $this->assertSame('J-12345678-9', Proveedor::first()->rif);

        $guardar(['nombre' => 'agro andes c.a.'])->assertSessionHasErrors(['nombre' => 'Ya hay un proveedor llamado «Agro Andes C.A.».']);
        $guardar(['nombre' => 'Otro', 'documento_tipo' => 'J', 'documento_numero' => '123456789'])
            ->assertSessionHasErrors(['documento_numero' => 'Ya hay un proveedor con ese RIF: Agro Andes C.A..']);

        $this->assertSame(1, Proveedor::count());
    }

    public function test_editar_el_proveedor_conserva_su_rif_sin_chocar(): void
    {
        $proveedor = Proveedor::create(['nombre' => 'Agro Andes', 'rif' => 'J-12345678-9']);

        $this->actingAs($this->encargado)->put(route('proveedores.update', $proveedor), [
            'nombre' => 'Agro Andes', 'documento_tipo' => 'J', 'documento_numero' => '123456789', 'telefono' => '04141234567', 'activo' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertSame('04141234567', $proveedor->fresh()->telefono);
    }

    // ---------- Clientes ----------

    public function test_dos_clientes_no_tienen_la_misma_cedula(): void
    {
        $guardar = fn (array $datos) => $this->actingAs($this->encargado)->post(route('clientes.store'), $datos);

        $guardar(['nombre' => 'Jose López', 'documento_tipo' => 'V', 'documento_numero' => '31.076.899'])->assertSessionHasNoErrors();
        $guardar(['nombre' => 'María', 'documento_tipo' => 'V', 'documento_numero' => '31076899'])
            ->assertSessionHasErrors(['documento_numero' => 'Ya hay un cliente con esa cédula o RIF: Jose López. Búscalo en la lista en vez de registrarlo de nuevo.']);

        // Mismo nombre con otra cédula sí: pueden ser dos personas distintas
        $guardar(['nombre' => 'Jose López', 'documento_tipo' => 'V', 'documento_numero' => '12345678'])->assertSessionHasNoErrors();

        $this->assertSame(2, Cliente::count());
    }

    public function test_no_se_puede_cambiar_la_cedula_de_un_cliente_por_la_de_otro(): void
    {
        Cliente::create(['nombre' => 'Jose', 'rif' => 'V-31076899', 'activo' => false]);
        $maria = Cliente::create(['nombre' => 'María', 'rif' => 'V-12345678']);

        $this->actingAs($this->encargado)->put(route('clientes.update', $maria), [
            'nombre' => 'María', 'documento_tipo' => 'V', 'documento_numero' => '31076899', 'activo' => 1,
        ])->assertSessionHasErrors('documento_numero');
        $this->assertStringContainsString('Está desactivado', session('errors')->first('documento_numero'));

        $this->actingAs($this->encargado)->put(route('clientes.update', $maria), [
            'nombre' => 'María Pérez', 'documento_tipo' => 'V', 'documento_numero' => '12345678', 'activo' => 1,
        ])->assertSessionHasNoErrors();
        $this->assertSame('V-12345678', $maria->fresh()->rif);
    }

    // ---------- Repetidos que ya estaban guardados ----------

    public function test_los_repetidos_existentes_se_corrigen_sin_borrar_nada(): void
    {
        $productos = Unicidad::corregir('productos', [
            ['id' => 8, 'nombre' => 'mora', 'marca' => null, 'unidad_medida' => 'kg', 'codigo_barras' => '123'],
            ['id' => 9, 'nombre' => 'Mora ', 'marca' => null, 'unidad_medida' => 'kg', 'codigo_barras' => '123'],
            ['id' => 10, 'nombre' => 'Mora', 'marca' => null, 'unidad_medida' => 'caja', 'codigo_barras' => ''],
        ]);
        $this->assertSame(['mora', 'Mora (repetido 2)', 'Mora'], array_column($productos, 'nombre'));
        $this->assertSame(['123', null, null], array_column($productos, 'codigo_barras'));

        $clientes = Unicidad::corregir('clientes', [
            ['id' => 17, 'nombre' => 'jose lopez', 'rif' => 'V-31076899', 'notas' => null],
            ['id' => 18, 'nombre' => 'MARIA', 'rif' => 'V-31076899', 'notas' => 'Paga los viernes'],
        ]);
        $this->assertSame('V-31076899', $clientes[0]['rif']);
        $this->assertNull($clientes[1]['rif']);
        $this->assertSame("Paga los viernes\n[Tenía la cédula/RIF V-31076899, que ya es del cliente jose lopez (#17). Escribe la correcta.]", $clientes[1]['notas']);
    }
}
