<?php

namespace Tests\Feature;

use App\Models\CierreCaja;
use App\Models\Cliente;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Fichas y formularios en ventana flotante:
 * 1. Tocar una fila de clientes, proveedores o caja pide su ficha por fetch (sin el layout).
 * 2. "Nuevo…" abre el formulario en una ventana; si el servidor lo devuelve con errores, la ventana se reabre sola.
 */
class VentanasFlotantesTest extends TestCase
{
    use RefreshDatabase;

    private User $encargado;
    private array $ajax = ['X-Requested-With' => 'XMLHttpRequest'];

    protected function setUp(): void
    {
        parent::setUp();
        CierreCaja::olvidarEstados();
        Http::fake();
        $this->encargado = User::factory()->create(['rol' => 'Encargado']);
    }

    public function test_la_fila_del_cliente_abre_su_ficha(): void
    {
        $cliente = Cliente::create(['nombre' => 'Rosa Pérez', 'limite_credito' => 50]);

        $this->actingAs($this->encargado)->get(route('clientes.index'))
            ->assertSee('data-ficha="' . route('clientes.show', $cliente) . '"', false)
            ->assertSee('data-abrir-ventana="ventana-cliente"', false);

        $this->actingAs($this->encargado)->get(route('clientes.show', $cliente), $this->ajax)
            ->assertOk()
            ->assertSee('Últimos fiados y abonos')
            ->assertSee('Crédito libre')
            ->assertDontSee('<html', false);

        // Abierta directo sigue siendo la cuenta completa, donde se cobra el abono
        $this->actingAs($this->encargado)->get(route('clientes.show', $cliente))
            ->assertOk()
            ->assertSee('Registrar abono');
    }

    public function test_la_fila_del_proveedor_abre_su_ficha(): void
    {
        $proveedor = Proveedor::create(['nombre' => 'Mercado Mayorista', 'activo' => true, 'da_credito' => true, 'dias_credito' => 15]);

        $this->actingAs($this->encargado)->get(route('proveedores.index'))
            ->assertSee('data-ficha="' . route('proveedores.show', $proveedor) . '"', false)
            ->assertSee('id="ventana-proveedor"', false);

        $this->actingAs($this->encargado)->get(route('proveedores.show', $proveedor), $this->ajax)
            ->assertOk()
            ->assertSee('Últimas compras')
            ->assertSee('Da crédito a 15 días')
            ->assertDontSee('<html', false);
    }

    public function test_la_fila_del_historial_de_caja_abre_su_cuadre(): void
    {
        $caja = CierreCaja::create([
            'user_id' => $this->encargado->id, 'fecha' => now()->subDay()->toDateString(), 'estado' => 'CERRADA',
            'monto_apertura' => 20, 'monto_apertura_ves' => 0, 'monto_efectivo_real' => 18, 'monto_efectivo_real_ves' => 0,
            'diferencia' => -2, 'diferencia_ves' => 0,
        ]);

        $this->actingAs($this->encargado)->get(route('caja.index'))
            ->assertSee('data-ficha="' . route('caja.show', $caja) . '"', false);

        $this->actingAs($this->encargado)->get(route('caja.show', $caja), $this->ajax)
            ->assertOk()
            ->assertSee('Esperado en caja')
            ->assertSee('Efectivo contado')
            ->assertSee(route('caja.reporte', $caja), false)
            ->assertDontSee('<html', false);
    }

    public function test_si_el_formulario_vuelve_con_errores_la_ventana_se_reabre(): void
    {
        $abierta = '/id="ventana-categoria" data-ventana\s+data-abrir-al-cargar/';
        $this->assertDoesNotMatchRegularExpression($abierta, $this->actingAs($this->encargado)->get(route('categorias.index'))->getContent());

        $this->actingAs($this->encargado)
            ->from(route('categorias.index'))
            ->post(route('categorias.store'), ['_ventana' => 'ventana-categoria', 'nombre' => ''])
            ->assertRedirect(route('categorias.index'))
            ->assertSessionHasErrors('nombre');

        $html = $this->actingAs($this->encargado)->get(route('categorias.index'))->getContent();
        $this->assertMatchesRegularExpression($abierta, $html);
    }

    public function test_nuevo_producto_se_registra_desde_la_ventana_del_listado(): void
    {
        $this->actingAs($this->encargado)->get(route('productos.index'))
            ->assertSee('data-abrir-ventana="ventana-producto"', false)
            ->assertSee('action="' . route('productos.store') . '"', false);

        $this->actingAs($this->encargado)
            ->from(route('productos.index'))
            ->post(route('productos.store'), ['_ventana' => 'ventana-producto', 'nombre' => 'Patilla'])
            ->assertRedirect(route('productos.index'))
            ->assertSessionHasErrors(['precio', 'categoria_id']);

        $html = $this->actingAs($this->encargado)->get(route('productos.index'))->getContent();
        $this->assertMatchesRegularExpression('/id="ventana-producto" data-ventana\s+data-abrir-al-cargar/', $html);
        $this->assertStringContainsString('value="Patilla"', $html);
    }

    public function test_desde_productos_la_compra_abre_con_el_producto_elegido(): void
    {
        $abierta = '/id="ventana-compra" data-ventana\s+data-abrir-al-cargar/';
        $this->assertDoesNotMatchRegularExpression($abierta, $this->actingAs($this->encargado)->get(route('compras.index'))->getContent());
        $this->assertMatchesRegularExpression($abierta, $this->actingAs($this->encargado)->get(route('compras.index', ['producto' => 1]))->getContent());
    }
}
