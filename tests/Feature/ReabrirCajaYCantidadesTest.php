<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\CierreCaja;
use App\Models\Compra;
use App\Models\Merma;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * 1. La caja de hoy se puede reabrir si se cerró por error (encargado o dueño), dejando rastro.
 * 2. Productos por unidad (unidad, caja, atado, saco) se manejan en cantidades enteras.
 */
class ReabrirCajaYCantidadesTest extends TestCase
{
    use RefreshDatabase;

    private User $encargado;

    protected function setUp(): void
    {
        parent::setUp();
        CierreCaja::olvidarEstados();
        Http::fake();
        $this->encargado = User::factory()->create(['rol' => 'Encargado', 'name' => 'Carmen']);
    }

    private function producto(string $unidad = 'unidad', float $stock = 50): Producto
    {
        return Producto::create([
            'categoria_id' => Categoria::firstOrCreate(['nombre' => 'Frutas'])->id,
            'nombre' => $unidad === 'kg' ? 'Papa' : 'Piña',
            'unidad_medida' => $unidad,
            'precio' => 2,
            'stock' => $stock,
            'stock_minimo' => 1,
        ]);
    }

    private function cajaDeHoy(string $estado = 'ABIERTA', array $datos = []): CierreCaja
    {
        return CierreCaja::create($datos + [
            'user_id' => $this->encargado->id, 'fecha' => now()->toDateString(), 'monto_apertura' => 20, 'estado' => $estado,
        ]);
    }

    private function vender(Producto $producto, $cantidad)
    {
        return $this->actingAs($this->encargado)->post(route('ventas.store'), [
            'productos' => [['id' => $producto->id, 'cantidad' => $cantidad]], 'tipo_pago' => 'contado',
        ]);
    }

    // ---------- Reabrir caja ----------

    public function test_el_encargado_reabre_la_caja_cerrada_y_se_puede_volver_a_vender(): void
    {
        $caja = $this->cajaDeHoy('CERRADA', ['monto_efectivo_real' => 25, 'monto_efectivo_real_ves' => 0, 'diferencia' => 5, 'diferencia_ves' => 0]);
        $producto = $this->producto();

        $this->vender($producto, 1)->assertSessionHasErrors('error'); // cerrada
        CierreCaja::olvidarEstados();

        $this->actingAs($this->encargado)->put(route('caja.reabrir', $caja))->assertSessionHasNoErrors();

        $caja->refresh();
        $this->assertSame('ABIERTA', $caja->estado);
        $this->assertNull($caja->monto_efectivo_real);
        $this->assertStringContainsString('Reabierta el', $caja->observaciones);
        $this->assertStringContainsString('por Carmen', $caja->observaciones);
        $this->assertStringContainsString('contado $25.00', $caja->observaciones);

        CierreCaja::olvidarEstados();
        $this->vender($producto, 1)->assertSessionHasNoErrors();
        $this->assertSame(1, Venta::count());
    }

    public function test_al_volver_a_cerrar_se_cuadra_de_nuevo_y_se_conserva_la_nota(): void
    {
        $caja = $this->cajaDeHoy('CERRADA', ['monto_efectivo_real' => 20, 'diferencia' => 0]);
        $this->actingAs($this->encargado)->put(route('caja.reabrir', $caja));
        CierreCaja::olvidarEstados();

        $this->vender($this->producto(), 3)->assertSessionHasNoErrors(); // $6 de contado

        $this->actingAs($this->encargado)
            ->put(route('caja.cerrar', $caja), ['monto_efectivo_real' => 26, 'observaciones' => 'Faltaba una venta'])
            ->assertSessionHasNoErrors();

        $caja->refresh();
        $this->assertSame('CERRADA', $caja->estado);
        $this->assertEquals(6, $caja->monto_ventas_sistema);
        $this->assertEquals(0, $caja->diferencia); // 20 fondo + 6 ventas = 26 contados
        $this->assertStringContainsString('Reabierta el', $caja->observaciones);
        $this->assertStringContainsString('Faltaba una venta', $caja->observaciones);
    }

    public function test_el_obrero_no_puede_reabrir_la_caja(): void
    {
        $obrero = User::factory()->create(['rol' => 'Obrero']);
        $caja = $this->cajaDeHoy('CERRADA');

        $this->actingAs($obrero)->put(route('caja.reabrir', $caja))->assertForbidden();
        $this->assertSame('CERRADA', $caja->fresh()->estado);
    }

    public function test_no_se_reabre_la_caja_de_un_dia_anterior(): void
    {
        $caja = $this->cajaDeHoy('CERRADA', ['fecha' => now()->subDay()->toDateString()]);

        $this->actingAs($this->encargado)->put(route('caja.reabrir', $caja))->assertSessionHasErrors('error');
        $this->assertSame('CERRADA', $caja->fresh()->estado);
    }

    public function test_la_pantalla_de_caja_cerrada_ofrece_reabrir_al_encargado(): void
    {
        $caja = $this->cajaDeHoy('CERRADA');

        $this->actingAs($this->encargado)->get(route('caja.index'))
            ->assertOk()
            ->assertSee(route('caja.reabrir', $caja), false)
            ->assertSee('Reabrir caja');

        $obrero = User::factory()->create(['rol' => 'Obrero']);
        $this->actingAs($obrero)->get(route('caja.index'))
            ->assertOk()
            ->assertDontSee(route('caja.reabrir', $caja), false)
            ->assertSee('Pide a un encargado que la reabra');
    }

    // ---------- Cantidades enteras ----------

    public function test_producto_por_unidad_no_se_vende_con_decimales(): void
    {
        $this->cajaDeHoy();
        $pina = $this->producto('unidad');

        $this->vender($pina, 1.5)->assertSessionHasErrors('error');
        $this->assertSame(0, Venta::count());
        $this->assertSame(50.0, $pina->fresh()->stock);

        $this->vender($pina, 2)->assertSessionHasNoErrors();
        $this->assertSame(48.0, $pina->fresh()->stock);
    }

    public function test_producto_por_peso_si_admite_decimales(): void
    {
        $this->cajaDeHoy();
        $papa = $this->producto('kg');

        $this->vender($papa, 1.25)->assertSessionHasNoErrors();
        $this->assertSame(48.75, $papa->fresh()->stock);
    }

    public function test_compras_y_mermas_por_unidad_van_sin_decimales(): void
    {
        $pina = $this->producto('caja');

        $this->actingAs($this->encargado)
            ->post(route('compras.store'), ['producto_id' => $pina->id, 'cantidad' => 2.5, 'precio_compra' => 1])
            ->assertSessionHasErrors('cantidad');
        $this->actingAs($this->encargado)
            ->post(route('mermas.store'), ['producto_id' => $pina->id, 'cantidad' => 0.5, 'motivo' => 'danado'])
            ->assertSessionHasErrors('cantidad');

        $this->assertSame(0, Compra::count() + Merma::count());
        $this->assertSame(50.0, $pina->fresh()->stock);

        $this->actingAs($this->encargado)
            ->post(route('compras.store'), ['producto_id' => $pina->id, 'cantidad' => 3, 'precio_compra' => 1])
            ->assertSessionHasNoErrors();
        $this->assertSame(53.0, $pina->fresh()->stock);
    }

    public function test_existencias_de_producto_por_unidad_van_sin_decimales(): void
    {
        $datos = [
            'nombre' => 'Huevos', 'categoria_id' => Categoria::firstOrCreate(['nombre' => 'Otros'])->id,
            'unidad_medida' => 'unidad', 'precio' => 0.3, 'stock_minimo' => 5,
        ];

        $this->actingAs($this->encargado)->post(route('productos.store'), $datos + ['stock' => 10.5])
            ->assertSessionHasErrors('stock');
        $this->assertSame(0, Producto::count());

        $this->actingAs($this->encargado)->post(route('productos.store'), ['unidad_medida' => 'kg', 'stock' => 10.5] + $datos)
            ->assertSessionHasNoErrors();
        $this->assertSame(10.5, Producto::first()->stock);
    }

    public function test_el_formulario_de_venta_marca_las_cantidades_enteras(): void
    {
        $this->cajaDeHoy();
        $pina = $this->producto('unidad');
        $papa = $this->producto('kg');

        $html = $this->actingAs($this->encargado)->get(route('ventas.create'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<option value="' . $pina->id . '"[^>]*data-entero="1"/', $html);
        $this->assertMatchesRegularExpression('/<option value="' . $papa->id . '"[^>]*data-entero="0"/', $html);

        // Sin producto elegido no se restringe todavía: la regla la pone la unidad del producto
        $this->assertStringContainsString('name="productos[0][cantidad]" min="0.001" step="0.001" inputmode="decimal" data-entero="0" value="1"', $html);

        // En la venta no se muestran textos de ejemplo bajo la cantidad
        $this->assertStringNotContainsString(\App\Models\Producto::AYUDA_SIN_PRODUCTO, $html);
        $this->assertStringNotContainsString('sin decimales (ej. 3)', $html);
    }

    public function test_al_editar_una_venta_la_cantidad_sigue_la_unidad_del_producto(): void
    {
        $this->cajaDeHoy();
        $pina = $this->producto('unidad');
        $this->vender($pina, 2)->assertSessionHasNoErrors();
        $venta = Venta::first();

        $html = $this->actingAs($this->encargado)->get(route('ventas.edit', $venta))->assertOk()->getContent();

        $this->assertStringContainsString('name="productos[0][cantidad]" min="1" step="1" inputmode="numeric" data-entero="1" value="2"', $html);
        $this->assertStringNotContainsString('sin decimales (ej. 3)', $html);
    }

    public function test_compras_y_mermas_ajustan_la_cantidad_segun_el_producto(): void
    {
        $this->producto('unidad');

        $this->actingAs($this->encargado)->get(route('compras.index'))
            ->assertOk()
            ->assertSee('id="compra-ayuda-cantidad"', false)
            ->assertSee('data-ayuda="Se cuenta por Unidad: sin decimales (ej. 3)."', false);

        $this->actingAs($this->encargado)->get(route('mermas.index'))
            ->assertOk()
            ->assertSee('id="merma-ayuda-cantidad"', false)
            ->assertSee('data-ayuda="Se cuenta por Unidad: sin decimales (ej. 3)."', false);
    }

    public function test_el_formulario_de_producto_explica_la_regla_de_su_unidad(): void
    {
        $this->actingAs($this->encargado)->get(route('productos.edit', $this->producto('kg')))
            ->assertOk()
            ->assertSee('Se mide en Kilogramo (Kg): admite decimales (ej. 2.5).', false);

        $this->actingAs($this->encargado)->get(route('productos.edit', $this->producto('saco')))
            ->assertOk()
            ->assertSee('Se cuenta por Saco: sin decimales (ej. 3).', false);
    }

    public function test_las_cantidades_se_muestran_sin_ceros_de_sobra(): void
    {
        $this->assertSame('3', \App\Support\Cantidad::formato('3.000'));
        $this->assertSame('2.5', \App\Support\Cantidad::formato('2.500'));
        $this->assertSame('0.125', \App\Support\Cantidad::formato(0.125));
        $this->assertSame('0', \App\Support\Cantidad::formato(null));
        $this->assertSame('-3', \App\Support\Cantidad::formato(-3));

        // En el historial de compras: +3, no +3.000
        $pina = $this->producto('unidad');
        $this->actingAs($this->encargado)
            ->post(route('compras.store'), ['producto_id' => $pina->id, 'cantidad' => 3, 'precio_compra' => 1])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->encargado)->get(route('compras.index'))
            ->assertOk()
            ->assertSee('+3<', false)
            ->assertDontSee('+3.000', false);
    }
}
