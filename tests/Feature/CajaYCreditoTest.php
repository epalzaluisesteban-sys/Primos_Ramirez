<?php

namespace Tests\Feature;

use App\Models\Abono;
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
 * 1. Las ventas solo se registran con la caja del día ABIERTA.
 * 2. Crédito disponible de clientes fiados = límite − saldo pendiente.
 */
class CajaYCreditoTest extends TestCase
{
    use RefreshDatabase;

    private User $obrero;

    protected function setUp(): void
    {
        parent::setUp();
        CierreCaja::olvidarEstados();
        Http::fake();
        $this->obrero = User::factory()->create(['rol' => 'Obrero']);
    }

    private function producto(float $precio = 2, float $stock = 50): Producto
    {
        return Producto::create([
            'categoria_id' => Categoria::firstOrCreate(['nombre' => 'Frutas'])->id,
            'nombre' => 'Cambur',
            'unidad_medida' => 'kg',
            'precio' => $precio,
            'stock' => $stock,
            'stock_minimo' => 1,
        ]);
    }

    private function abrirCaja(): void
    {
        CierreCaja::create(['user_id' => $this->obrero->id, 'fecha' => now()->toDateString(), 'monto_apertura' => 10, 'estado' => 'ABIERTA']);
    }

    private function vender(Producto $producto, float $cantidad, array $extra = [])
    {
        return $this->actingAs($this->obrero)->post(route('ventas.store'), $extra + [
            'productos' => [['id' => $producto->id, 'cantidad' => $cantidad]],
            'tipo_pago' => 'contado',
        ]);
    }

    // ---------- Caja abierta ----------

    public function test_sin_caja_abierta_no_se_registra_la_venta(): void
    {
        $producto = $this->producto();

        $this->vender($producto, 1)
            ->assertRedirect(route('caja.index'))
            ->assertSessionHasErrors('error');

        $this->assertSame(0, Venta::count());
        $this->assertSame(50.0, $producto->fresh()->stock);
    }

    public function test_sin_caja_abierta_la_pantalla_de_venta_lleva_a_abrir_la_caja(): void
    {
        $this->actingAs($this->obrero)->get(route('ventas.create'))
            ->assertRedirect(route('caja.index'))
            ->assertSessionHasErrors('error');
    }

    public function test_una_caja_de_otro_dia_no_sirve_para_vender_hoy(): void
    {
        CierreCaja::create(['user_id' => $this->obrero->id, 'fecha' => now()->subDay()->toDateString(), 'monto_apertura' => 0, 'estado' => 'ABIERTA']);

        $this->vender($this->producto(), 1)->assertSessionHasErrors('error');
        $this->assertSame(0, Venta::count());
    }

    public function test_con_la_caja_abierta_si_se_vende(): void
    {
        $this->abrirCaja();
        $producto = $this->producto();

        $this->vender($producto, 2)->assertSessionHasNoErrors();

        $this->assertSame(1, Venta::count());
        $this->assertSame(48.0, $producto->fresh()->stock);
    }

    public function test_al_abrir_la_caja_se_puede_vender(): void
    {
        $this->actingAs($this->obrero)->post(route('caja.abrir'), ['monto_apertura' => 20])->assertSessionHasNoErrors();
        CierreCaja::olvidarEstados(); // nueva petición

        $this->actingAs($this->obrero)->get(route('ventas.create'))->assertOk();
        $this->vender($this->producto(), 1)->assertSessionHasNoErrors();
        $this->assertSame(1, Venta::count());
    }

    // ---------- Abonos también con la caja abierta ----------

    private function clienteQueDebe(float $deuda = 20): Cliente
    {
        $cliente = Cliente::create(['nombre' => 'Doña Rosa']);
        Venta::create([
            'user_id' => $this->obrero->id, 'cliente_id' => $cliente->id, 'fecha' => now(),
            'tipo_pago' => 'credito', 'moneda_pago' => 'USD', 'total' => $deuda,
        ]);

        return $cliente;
    }

    public function test_sin_caja_abierta_no_se_registra_el_abono(): void
    {
        $encargado = User::factory()->create(['rol' => 'Encargado']);
        $cliente = $this->clienteQueDebe();

        $this->actingAs($encargado)
            ->post(route('clientes.abonos.store', $cliente), ['monto' => 5])
            ->assertSessionHasErrors('error');

        $this->assertSame(0, $cliente->abonos()->count());
        $this->assertSame(20.0, $cliente->saldoPendiente());
    }

    public function test_con_la_caja_abierta_si_se_registra_el_abono(): void
    {
        $encargado = User::factory()->create(['rol' => 'Encargado']);
        $cliente = $this->clienteQueDebe();
        $this->abrirCaja();

        $this->actingAs($encargado)
            ->post(route('clientes.abonos.store', $cliente), ['monto' => 5])
            ->assertSessionHasNoErrors();

        $this->assertSame(15.0, $cliente->saldoPendiente());
    }

    public function test_la_ficha_del_cliente_pide_abrir_la_caja_antes_del_abono(): void
    {
        $encargado = User::factory()->create(['rol' => 'Encargado']);
        $cliente = $this->clienteQueDebe();

        $this->actingAs($encargado)->get(route('clientes.show', $cliente))
            ->assertOk()
            ->assertSee('Ir a abrir la caja')
            ->assertDontSee(route('clientes.abonos.store', $cliente), false);

        $this->abrirCaja();
        CierreCaja::olvidarEstados();

        $this->actingAs($encargado)->get(route('clientes.show', $cliente))
            ->assertOk()
            ->assertDontSee('Ir a abrir la caja')
            ->assertSee(route('clientes.abonos.store', $cliente), false);
    }

    // ---------- Crédito disponible ----------

    public function test_credito_disponible_es_limite_menos_saldo(): void
    {
        $cliente = Cliente::create(['nombre' => 'Ana', 'limite_credito' => 50]);
        $this->assertSame(50.0, $cliente->creditoDisponible());

        $this->abrirCaja();
        $this->vender($this->producto(2), 10, ['tipo_pago' => 'credito', 'cliente_id' => $cliente->id])->assertSessionHasNoErrors();
        $this->assertSame(30.0, $cliente->fresh()->creditoDisponible()); // debe $20

        Abono::create(['cliente_id' => $cliente->id, 'user_id' => $this->obrero->id, 'monto' => 5, 'moneda' => 'USD', 'monto_moneda' => 5, 'fecha' => now()]);
        $this->assertSame(35.0, $cliente->fresh()->creditoDisponible());
    }

    public function test_sin_limite_el_credito_disponible_es_ilimitado_y_nunca_negativo(): void
    {
        $this->assertNull(Cliente::create(['nombre' => 'Sin límite'])->creditoDisponible());

        $cliente = Cliente::create(['nombre' => 'Pasado', 'limite_credito' => 10]);
        $this->assertSame(0.0, $cliente->creditoDisponible(25)); // debe más que su límite
    }

    public function test_venta_que_supera_el_credito_disponible_se_rechaza_con_el_disponible_en_el_mensaje(): void
    {
        $this->abrirCaja();
        $cliente = Cliente::create(['nombre' => 'Ana', 'limite_credito' => 10]);

        $respuesta = $this->vender($this->producto(2), 6, ['tipo_pago' => 'credito', 'cliente_id' => $cliente->id]); // $12

        $respuesta->assertSessionHasErrors('error');
        $this->assertStringContainsString('solo se le pueden fiar $10.00 más', session('errors')->first('error'));
        $this->assertSame(0, Venta::count());
    }

    public function test_la_pantalla_de_venta_muestra_el_credito_disponible_de_cada_cliente(): void
    {
        $this->abrirCaja();
        $conLimite = Cliente::create(['nombre' => 'Ana', 'limite_credito' => 50]);
        Cliente::create(['nombre' => 'Beto']);

        $html = $this->actingAs($this->obrero)->get(route('ventas.create'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<option value="' . $conLimite->id . '"[^>]*data-disponible="50"/', $html);
        $this->assertStringContainsString('Disponible: $50.00', $html);
        $this->assertStringContainsString('id="credito-info"', $html);
    }

    public function test_listado_y_ficha_del_cliente_muestran_el_credito_disponible(): void
    {
        $encargado = User::factory()->create(['rol' => 'Encargado']);
        $ana = Cliente::create(['nombre' => 'Ana', 'limite_credito' => 40]);
        Cliente::create(['nombre' => 'Beto']);

        $this->actingAs($encargado)->get(route('clientes.index'))
            ->assertOk()
            ->assertSee('Crédito disponible')
            ->assertSee('Sin límite')
            ->assertSee('$40.00');

        $this->actingAs($encargado)->get(route('clientes.show', $ana))
            ->assertOk()
            ->assertSee('Crédito disponible')
            ->assertSee('Límite de crédito: $40.00', false);
    }
}
