<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\CierreCaja;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * 1. El cliente se puede elegir también en ventas de contado y registrar sin salir de la venta.
 * 2. El detalle de la venta se pide por fetch para la ventana flotante.
 * 3. Eliminar manda la venta a la papelera (anulada) y se puede restaurar si es de hoy con la caja abierta.
 */
class VentasClienteYPapeleraTest extends TestCase
{
    use RefreshDatabase;

    private User $obrero;
    private User $encargado;

    protected function setUp(): void
    {
        parent::setUp();
        CierreCaja::olvidarEstados();
        Http::fake();
        $this->obrero = User::factory()->create(['rol' => 'Obrero']);
        $this->encargado = User::factory()->create(['rol' => 'Encargado']);
        CierreCaja::create(['user_id' => $this->obrero->id, 'fecha' => now()->toDateString(), 'monto_apertura' => 0, 'estado' => 'ABIERTA']);
    }

    private function producto(float $stock = 20): Producto
    {
        return Producto::create([
            'categoria_id' => Categoria::firstOrCreate(['nombre' => 'Frutas'])->id,
            'nombre' => 'Mango',
            'unidad_medida' => 'kg',
            'precio' => 2,
            'stock' => $stock,
            'stock_minimo' => 1,
        ]);
    }

    private function vender(Producto $producto, float $cantidad, array $extra = []): Venta
    {
        $this->actingAs($this->obrero)->post(route('ventas.store'), $extra + [
            'productos' => [['id' => $producto->id, 'cantidad' => $cantidad]],
            'tipo_pago' => 'contado',
        ])->assertSessionHasNoErrors();

        return Venta::latest('id')->first();
    }

    // ---------- Cliente en la venta ----------

    public function test_venta_de_contado_guarda_el_cliente_sin_sumarle_deuda(): void
    {
        $cliente = Cliente::create(['nombre' => 'Ana Ruiz']);

        $venta = $this->vender($this->producto(), 3, ['cliente_id' => $cliente->id]);

        $this->assertSame($cliente->id, $venta->cliente_id);
        $this->assertSame(0.0, $cliente->saldoPendiente());
        $this->assertEquals(6, $cliente->totalComprado());
    }

    public function test_venta_de_contado_sin_cliente_sigue_siendo_valida(): void
    {
        $venta = $this->vender($this->producto(), 1);

        $this->assertNull($venta->cliente_id);
    }

    public function test_no_se_vende_a_un_cliente_desactivado(): void
    {
        $cliente = Cliente::create(['nombre' => 'Inactivo', 'activo' => false]);

        $this->actingAs($this->obrero)->post(route('ventas.store'), [
            'productos' => [['id' => $this->producto()->id, 'cantidad' => 1]],
            'tipo_pago' => 'contado',
            'cliente_id' => $cliente->id,
        ])->assertSessionHasErrors('cliente_id');
    }

    public function test_el_obrero_registra_un_cliente_desde_la_venta(): void
    {
        $respuesta = $this->actingAs($this->obrero)->postJson(route('clientes.rapido'), [
            'nombre' => 'Pedro Gómez',
            'telefono' => '0412-123-4567',
            'documento_tipo' => 'V',
            'documento_numero' => '12.345.678',
            'limite_credito' => 999, // no se toma: el límite lo pone un encargado
        ]);

        $respuesta->assertCreated()->assertJson(['nombre' => 'Pedro Gómez']);
        $cliente = Cliente::find($respuesta->json('id'));
        $this->assertSame('04121234567', $cliente->telefono);
        $this->assertNotNull($cliente->rif);
        $this->assertNull($cliente->limite_credito);
        $this->assertTrue($cliente->fresh()->activo);
    }

    public function test_cliente_rapido_valida_y_avisa_si_la_cedula_ya_existe(): void
    {
        $this->actingAs($this->obrero)->postJson(route('clientes.rapido'), ['nombre' => ''])
            ->assertStatus(422)->assertJsonValidationErrors('nombre');

        $this->actingAs($this->obrero)->postJson(route('clientes.rapido'), ['nombre' => 'Uno', 'documento_tipo' => 'V', 'documento_numero' => '12345678'])
            ->assertCreated();

        $this->actingAs($this->obrero)->postJson(route('clientes.rapido'), ['nombre' => 'Dos', 'documento_tipo' => 'V', 'documento_numero' => '12345678'])
            ->assertStatus(422)->assertJsonValidationErrors('documento_numero');

        $this->assertSame(1, Cliente::count());
    }

    public function test_la_pantalla_de_venta_trae_el_cliente_opcional_y_la_ventana_de_cliente_nuevo(): void
    {
        $html = $this->actingAs($this->obrero)->get(route('ventas.create'))->assertOk()->getContent();

        $this->assertStringContainsString('id="btn-cliente-nuevo"', $html);
        $this->assertStringContainsString('action="' . route('clientes.rapido') . '"', $html);
        $this->assertStringContainsString('Sin cliente (mostrador)', $html);
    }

    // ---------- Detalle en ventana flotante ----------

    public function test_el_detalle_por_fetch_trae_solo_el_contenido_de_la_ventana(): void
    {
        $venta = $this->vender($this->producto(), 2.5);

        $parcial = $this->actingAs($this->obrero)
            ->get(route('ventas.show', $venta), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->getContent();
        $this->assertStringContainsString('Mango', $parcial);
        $this->assertStringContainsString('2.5', $parcial);
        $this->assertStringNotContainsString('<html', $parcial);
        // El obrero no ve la opción de eliminar
        $this->assertStringNotContainsString(route('ventas.anular', $venta), $parcial);

        // Abierto directo es una página completa
        $this->actingAs($this->obrero)->get(route('ventas.show', $venta))->assertOk()->assertSee('Venta #' . $venta->id);
    }

    public function test_en_el_listado_editar_y_eliminar_se_ven_siempre_y_explican_por_que_no_se_pueden_usar(): void
    {
        $dueno = User::factory()->create(['rol' => 'Dueno']);
        $hoy = $this->vender($this->producto(), 1);
        $vieja = Venta::create(['user_id' => $this->obrero->id, 'fecha' => now()->subDays(5), 'total' => 2, 'tipo_pago' => 'contado']);

        $html = $this->actingAs($dueno)->get(route('ventas.index'))->assertOk()->getContent();

        // La fila abre el detalle; el ticket ya no está en la tabla (va dentro del detalle)
        $this->assertStringContainsString('data-url="' . route('ventas.show', $hoy) . '"', $html);
        $this->assertStringNotContainsString(route('ventas.ticket', $hoy), $html);

        $this->assertStringContainsString(route('ventas.edit', $hoy), $html);
        $this->assertStringNotContainsString(route('ventas.edit', $vieja), $html);
        $this->assertStringContainsString('data-motivo="Es una venta del ' . $vieja->fecha->format('d/m/Y'), $html);

        // El obrero no tiene la columna de acciones
        $this->actingAs($this->obrero)->get(route('ventas.index'))->assertOk()
            ->assertDontSee('data-motivo="', false)
            ->assertDontSee(route('ventas.edit', $hoy), false);
    }

    // ---------- Papelera ----------

    public function test_eliminar_manda_la_venta_a_la_papelera_y_devuelve_el_stock(): void
    {
        $producto = $this->producto(20);
        $venta = $this->vender($producto, 5);
        $this->assertEquals(15, $producto->fresh()->stock);

        $this->actingAs($this->encargado)
            ->put(route('ventas.anular', $venta), ['motivo_anulacion' => 'Cantidad equivocada'])
            ->assertRedirect(route('ventas.index'));

        $venta->refresh();
        $this->assertTrue($venta->anulada);
        $this->assertSame($this->encargado->id, $venta->anulada_por);
        $this->assertEquals(20, $producto->fresh()->stock);

        // Sale del listado y aparece en la papelera
        $this->actingAs($this->encargado)->get(route('ventas.index'))->assertOk()
            ->assertDontSee(route('ventas.show', $venta), false);
        $this->actingAs($this->encargado)->get(route('ventas.papelera'))->assertOk()
            ->assertSee('Cantidad equivocada')
            ->assertSee(route('ventas.restaurar', $venta), false);
    }

    public function test_el_obrero_no_elimina_ni_ve_la_papelera(): void
    {
        $venta = $this->vender($this->producto(), 1);

        $this->actingAs($this->obrero)->put(route('ventas.anular', $venta))->assertForbidden();
        $this->actingAs($this->obrero)->get(route('ventas.papelera'))->assertForbidden();

        $venta->update(['anulada' => true]);
        $this->actingAs($this->obrero)->get(route('ventas.show', $venta))->assertForbidden();
    }

    public function test_restaurar_vuelve_a_descontar_el_stock(): void
    {
        $producto = $this->producto(20);
        $venta = $this->vender($producto, 5);
        $this->actingAs($this->encargado)->put(route('ventas.anular', $venta));

        $this->actingAs($this->encargado)->put(route('ventas.restaurar', $venta))
            ->assertRedirect(route('ventas.index'))->assertSessionHasNoErrors();

        $venta->refresh();
        $this->assertFalse($venta->anulada);
        $this->assertNull($venta->anulada_por);
        $this->assertNull($venta->motivo_anulacion);
        $this->assertEquals(15, $producto->fresh()->stock);
    }

    public function test_no_se_restaura_si_ya_no_alcanza_la_mercancia(): void
    {
        $producto = $this->producto(10);
        $venta = $this->vender($producto, 6);
        $this->actingAs($this->encargado)->put(route('ventas.anular', $venta));

        // Mientras estaba en la papelera se vendió casi todo
        $this->vender($producto, 8);

        $this->actingAs($this->encargado)->put(route('ventas.restaurar', $venta))
            ->assertSessionHasErrors('error');

        $this->assertTrue($venta->fresh()->anulada);
        $this->assertEquals(2, $producto->fresh()->stock);
    }

    public function test_no_se_restaura_una_venta_fiada_que_supera_el_credito(): void
    {
        $cliente = Cliente::create(['nombre' => 'Luis', 'limite_credito' => 10]);
        $producto = $this->producto(20);
        $venta = $this->vender($producto, 4, ['tipo_pago' => 'credito', 'cliente_id' => $cliente->id]); // $8
        $this->actingAs($this->encargado)->put(route('ventas.anular', $venta));
        $this->vender($producto, 4, ['tipo_pago' => 'credito', 'cliente_id' => $cliente->id]); // otros $8

        $this->actingAs($this->encargado)->put(route('ventas.restaurar', $venta))
            ->assertSessionHasErrors('error');

        $this->assertTrue($venta->fresh()->anulada);
        $this->assertEquals(16, $producto->fresh()->stock); // 20 − 4 (devueltos al eliminar) − 4 de la otra venta
    }

    public function test_no_se_restaura_una_venta_de_otro_dia_ni_con_la_caja_cerrada(): void
    {
        $producto = $this->producto(20);
        $ayer = Venta::create([
            'user_id' => $this->obrero->id, 'fecha' => now()->subDay(), 'total' => 2,
            'tipo_pago' => 'contado', 'anulada' => true, 'anulada_at' => now()->subDay(),
        ]);

        $this->actingAs($this->encargado)->put(route('ventas.restaurar', $ayer))->assertSessionHasErrors('error');
        $this->assertTrue($ayer->fresh()->anulada);

        $hoy = $this->vender($producto, 1);
        $this->actingAs($this->encargado)->put(route('ventas.anular', $hoy));
        CierreCaja::whereDate('fecha', now()->toDateString())->update(['estado' => 'CERRADA']);
        CierreCaja::olvidarEstados();

        $this->actingAs($this->encargado)->put(route('ventas.restaurar', $hoy))->assertSessionHasErrors('error');
        $this->assertTrue($hoy->fresh()->anulada);
    }
}
