<?php

namespace Tests\Feature;

use App\Models\AjusteInventario;
use App\Models\Categoria;
use App\Models\CierreCaja;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas de los arreglos de integridad: historial protegido, ajustes de stock en el kardex,
 * ventas/abonos bloqueados con la caja cerrada y cierre de caja por la fecha de la caja.
 */
class IntegridadHistorialTest extends TestCase
{
    use RefreshDatabase;

    private User $dueno;

    protected function setUp(): void
    {
        parent::setUp();
        CierreCaja::olvidarEstados();
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
            'stock' => 10,
            'stock_minimo' => 1,
        ]);
    }

    private function venta(Producto $producto, float $cantidad, $fecha, array $datos = []): Venta
    {
        $venta = Venta::create($datos + [
            'user_id' => $this->dueno->id,
            'fecha' => $fecha,
            'total' => $producto->precio * $cantidad,
            'tipo_pago' => 'contado',
            'moneda_pago' => 'USD',
        ]);
        DetalleVenta::create([
            'venta_id' => $venta->id,
            'producto_id' => $producto->id,
            'cantidad' => $cantidad,
            'precio_unitario' => $producto->precio,
            'subtotal' => $producto->precio * $cantidad,
        ]);

        return $venta;
    }

    private function cerrarCajaDeHoy(): void
    {
        CierreCaja::create([
            'user_id' => $this->dueno->id,
            'fecha' => now()->toDateString(),
            'monto_apertura' => 0,
            'estado' => 'CERRADA',
        ]);
    }

    // ---------- 1. Usuarios ----------

    public function test_no_se_elimina_un_usuario_con_movimientos(): void
    {
        $obrero = User::factory()->create(['rol' => 'Obrero']);
        Compra::create([
            'producto_id' => $this->producto()->id,
            'user_id' => $obrero->id,
            'cantidad' => 5,
            'precio_compra' => 1,
            'total' => 5,
            'fecha' => now(),
        ]);

        $this->actingAs($this->dueno)
            ->delete(route('usuarios.destroy', $obrero))
            ->assertSessionHasErrors('error');

        $this->assertModelExists($obrero);
        $this->assertSame(1, Compra::count());
    }

    public function test_se_elimina_un_usuario_sin_movimientos(): void
    {
        $obrero = User::factory()->create(['rol' => 'Obrero']);

        $this->actingAs($this->dueno)
            ->delete(route('usuarios.destroy', $obrero))
            ->assertSessionHasNoErrors();

        $this->assertModelMissing($obrero);
    }

    public function test_la_base_de_datos_impide_borrar_historial_en_cascada(): void
    {
        $obrero = User::factory()->create(['rol' => 'Obrero']);
        Compra::create([
            'producto_id' => $this->producto()->id,
            'user_id' => $obrero->id,
            'cantidad' => 5,
            'precio_compra' => 1,
            'total' => 5,
            'fecha' => now(),
        ]);

        $this->expectException(QueryException::class);
        $obrero->delete();
    }

    // ---------- 2. Productos ----------

    public function test_no_se_elimina_un_producto_con_ventas_y_se_puede_desactivar(): void
    {
        $producto = $this->producto();
        $this->venta($producto, 1, now());

        $this->actingAs($this->dueno)
            ->delete(route('productos.destroy', $producto))
            ->assertSessionHasErrors('error');
        $this->assertModelExists($producto);

        $this->actingAs($this->dueno)
            ->put(route('productos.update', $producto), $this->datosFormulario($producto, ['stock' => 10]))
            ->assertSessionHasNoErrors();

        $this->assertFalse($producto->fresh()->activo);
        $this->assertSame(0, Producto::agotados()->count() + Producto::stockCritico()->count());
    }

    public function test_no_se_vende_un_producto_desactivado(): void
    {
        $producto = $this->producto(['activo' => false]);
        // Con la caja abierta, para que el rechazo sea por el producto y no por la caja
        CierreCaja::create(['user_id' => $this->dueno->id, 'fecha' => now()->toDateString(), 'monto_apertura' => 0, 'estado' => 'ABIERTA']);

        $this->actingAs($this->dueno)
            ->post(route('ventas.store'), ['productos' => [['id' => $producto->id, 'cantidad' => 1]], 'tipo_pago' => 'contado'])
            ->assertSessionHasErrors('error');

        $this->assertSame(0, Venta::count());
        $this->assertSame(10.0, $producto->fresh()->stock);
    }

    // ---------- 3. Stock y kardex ----------

    public function test_crear_y_editar_stock_queda_registrado_y_el_kardex_cuadra(): void
    {
        $categoria = Categoria::create(['nombre' => 'Verduras']);

        $this->actingAs($this->dueno)->post(route('productos.store'), [
            'nombre' => 'Papa',
            'categoria_id' => $categoria->id,
            'unidad_medida' => 'kg',
            'precio' => 1.5,
            'stock' => 20,
            'stock_minimo' => 2,
        ])->assertSessionHasNoErrors();

        $producto = Producto::where('nombre', 'Papa')->firstOrFail();
        $this->assertSame(20.0, $producto->stock);
        $this->assertSame(1, $producto->ajustes()->count());

        $this->venta($producto, 3, now());
        $producto->decrement('stock', 3);

        $this->actingAs($this->dueno)
            ->put(route('productos.update', $producto), $this->datosFormulario($producto->fresh(), [
                'stock' => 15, 'activo' => 1, 'motivo_ajuste' => 'Conteo físico',
            ]))
            ->assertSessionHasNoErrors();

        $ajuste = AjusteInventario::latest('id')->first();
        $this->assertSame(-2.0, $ajuste->cantidad);
        $this->assertSame('Conteo físico', $ajuste->motivo);
        $this->assertSame(15.0, $producto->fresh()->stock);

        // El kardex (exportado) termina en la misma existencia que el stock del sistema
        $csv = $this->actingAs($this->dueno)->get(route('productos.kardex.excel', $producto))->streamedContent();
        $this->assertStringContainsString('Conteo físico', $csv);
        $filas = array_slice(array_map('str_getcsv', array_filter(explode("\n", trim(substr($csv, 3))))), 1);
        $existencia = array_sum(array_map(fn ($f) => (float) $f[2] - (float) $f[3], $filas)); // entradas - salidas
        $this->assertEquals(15, $existencia);
        $this->assertCount(3, $filas); // inventario inicial, venta y ajuste por conteo
    }

    public function test_editar_otros_campos_no_genera_ajuste(): void
    {
        $producto = $this->producto();

        $this->actingAs($this->dueno)
            ->put(route('productos.update', $producto), $this->datosFormulario($producto, ['precio' => 3, 'activo' => 1]))
            ->assertSessionHasNoErrors();

        $this->assertSame(0, AjusteInventario::count());
        $this->assertEquals(3, $producto->fresh()->precio);
    }

    // ---------- 4. Caja cerrada ----------

    public function test_con_la_caja_cerrada_no_se_anula_ni_se_vende(): void
    {
        $producto = $this->producto();
        $venta = $this->venta($producto, 2, now());
        $this->cerrarCajaDeHoy();

        $this->assertFalse($venta->fresh()->puedeEditarse());

        $this->actingAs($this->dueno)
            ->put(route('ventas.anular', $venta))
            ->assertSessionHasErrors('error');
        $this->assertFalse($venta->fresh()->anulada);

        $this->actingAs($this->dueno)
            ->post(route('ventas.store'), ['productos' => [['id' => $producto->id, 'cantidad' => 1]], 'tipo_pago' => 'contado'])
            ->assertSessionHasErrors('error');
        $this->assertSame(1, Venta::count());
    }

    public function test_con_la_caja_abierta_si_se_puede_anular(): void
    {
        $producto = $this->producto(['stock' => 8]);
        $venta = $this->venta($producto, 2, now());

        $this->actingAs($this->dueno)
            ->put(route('ventas.anular', $venta))
            ->assertSessionHasNoErrors();

        $this->assertTrue($venta->fresh()->anulada);
        $this->assertSame(10.0, $producto->fresh()->stock);
    }

    public function test_con_la_caja_cerrada_no_se_registran_abonos(): void
    {
        $cliente = Cliente::create(['nombre' => 'Doña Rosa']);
        $this->venta($this->producto(), 5, now(), ['tipo_pago' => 'credito', 'cliente_id' => $cliente->id]);
        $this->cerrarCajaDeHoy();

        $this->actingAs($this->dueno)
            ->post(route('clientes.abonos.store', $cliente), ['monto' => 5])
            ->assertSessionHasErrors('error');

        $this->assertSame(0, $cliente->abonos()->count());
    }

    // ---------- 5. Cierre por la fecha de la caja ----------

    public function test_el_cierre_cuadra_con_los_movimientos_del_dia_de_la_caja(): void
    {
        $producto = $this->producto();
        $ayer = now()->subDay();

        $caja = CierreCaja::create([
            'user_id' => $this->dueno->id,
            'fecha' => $ayer->toDateString(),
            'monto_apertura' => 20,
            'estado' => 'ABIERTA',
        ]);
        $this->venta($producto, 5, $ayer->copy()->setTime(10, 0)); // $10 ayer
        $this->venta($producto, 1, now());                          // $2 hoy: no cuenta

        $this->actingAs($this->dueno)
            ->put(route('caja.cerrar', $caja), ['monto_efectivo_real' => 30])
            ->assertSessionHasNoErrors();

        $caja->refresh();
        $this->assertEquals(10, $caja->monto_ventas_sistema);
        $this->assertEquals(0, $caja->diferencia);
        $this->assertSame('CERRADA', $caja->estado);
    }

    private function datosFormulario(Producto $producto, array $cambios = []): array
    {
        return $cambios + [
            'nombre' => $producto->nombre,
            'categoria_id' => $producto->categoria_id,
            'unidad_medida' => $producto->unidad_medida,
            'precio' => $producto->precio,
            'stock' => $producto->stock,
            'stock_minimo' => $producto->stock_minimo,
        ];
    }
}
