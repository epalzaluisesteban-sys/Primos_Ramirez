<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\CierreCaja;
use App\Models\Compra;
use App\Models\PagoProveedor;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\TasaCambio;
use App\Models\User;
use App\Services\CajaService;
use App\Services\TasaCambioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * 1. Cada proveedor dice qué categorías distribuye y si da crédito (y a cuántos días).
 * 2. Compras de contado o a crédito; a crédito solo con un proveedor que da crédito.
 * 3. Lo que se le debe = compras a crédito − pagos; los pagos no pasan de la deuda.
 * 4. Un pago con el efectivo de la caja se resta del cuadre del día.
 */
class ProveedoresCreditoTest extends TestCase
{
    use RefreshDatabase;

    private User $encargado;
    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();
        CierreCaja::olvidarEstados();
        TasaCambioService::olvidar();
        Http::fake();
        $this->encargado = User::factory()->create(['rol' => 'Encargado']);
        $this->producto = Producto::create([
            'categoria_id' => Categoria::create(['nombre' => 'Frutas'])->id,
            'nombre' => 'Mango', 'unidad_medida' => 'kg', 'precio' => 3, 'stock' => 0, 'stock_minimo' => 1,
        ]);
    }

    private function proveedor(array $datos = []): Proveedor
    {
        return Proveedor::create($datos + ['nombre' => 'Agro Andes', 'da_credito' => true, 'dias_credito' => 15]);
    }

    private function comprar(Proveedor $proveedor, string $tipoPago, float $cantidad = 10, float $precio = 2)
    {
        return $this->actingAs($this->encargado)->post(route('compras.store'), [
            'producto_id' => $this->producto->id, 'cantidad' => $cantidad, 'precio_compra' => $precio,
            'proveedor_id' => $proveedor->id, 'tipo_pago' => $tipoPago,
        ]);
    }

    private function pagar(Proveedor $proveedor, array $datos = [])
    {
        return $this->actingAs($this->encargado)->post(route('proveedores.pagos.store', $proveedor), $datos + [
            'monto' => 5, 'moneda' => 'USD', 'origen' => 'otro',
        ]);
    }

    // ---------- Qué distribuye y crédito ----------

    public function test_el_proveedor_guarda_lo_que_distribuye_y_su_credito(): void
    {
        $verduras = Categoria::create(['nombre' => 'Verduras']);
        $frutas = Categoria::where('nombre', 'Frutas')->first();

        $this->actingAs($this->encargado)->post(route('proveedores.store'), [
            'nombre' => 'Hortalizas del Valle', 'categorias' => [$frutas->id, $verduras->id], 'da_credito' => 1, 'dias_credito' => 30,
        ])->assertSessionHasNoErrors();

        $proveedor = Proveedor::first();
        $this->assertEqualsCanonicalizing(['Frutas', 'Verduras'], $proveedor->categorias->pluck('nombre')->all());
        $this->assertTrue($proveedor->da_credito);
        $this->assertSame(30, $proveedor->dias_credito);

        // Al quitarle el crédito se borran los días; al desmarcar una categoría deja de salir
        $this->actingAs($this->encargado)->put(route('proveedores.update', $proveedor), [
            'nombre' => 'Hortalizas del Valle', 'categorias' => [$verduras->id], 'da_credito' => 0, 'dias_credito' => 30, 'activo' => 1,
        ])->assertSessionHasNoErrors();

        $proveedor->refresh();
        $this->assertSame(['Verduras'], $proveedor->categorias->pluck('nombre')->all());
        $this->assertFalse($proveedor->da_credito);
        $this->assertNull($proveedor->dias_credito);
    }

    public function test_los_dias_de_credito_y_las_categorias_se_validan(): void
    {
        $this->actingAs($this->encargado)->post(route('proveedores.store'), [
            'nombre' => 'X Proveedor', 'categorias' => [999], 'da_credito' => 1, 'dias_credito' => 0,
        ])->assertSessionHasErrors(['categorias.0', 'dias_credito']);

        $this->assertSame(0, Proveedor::count());
    }

    // ---------- Compras a crédito ----------

    public function test_compra_a_credito_queda_como_deuda_con_su_vencimiento(): void
    {
        $proveedor = $this->proveedor();

        $this->comprar($proveedor, 'credito')->assertSessionHasNoErrors();

        $compra = Compra::first();
        $this->assertSame('credito', $compra->tipo_pago);
        $this->assertSame(today()->addDays(15)->toDateString(), $compra->vence_el->toDateString());
        $this->assertEquals(10, $this->producto->fresh()->stock);
        $this->assertEquals(20, $proveedor->estadoDeuda()['saldo']);

        $this->actingAs($this->encargado)->get(route('proveedores.show', $proveedor))
            ->assertOk()
            ->assertSee('Vence el ' . today()->addDays(15)->format('d/m/Y'))
            ->assertSee(route('proveedores.pagos.store', $proveedor), false);
    }

    public function test_compra_de_contado_no_suma_deuda(): void
    {
        $proveedor = $this->proveedor();

        $this->comprar($proveedor, 'contado')->assertSessionHasNoErrors();

        $this->assertEquals(0, $proveedor->estadoDeuda()['saldo']);
        $this->assertNull(Compra::first()->vence_el);
    }

    public function test_no_se_compra_a_credito_a_quien_no_da_credito_ni_sin_proveedor(): void
    {
        $this->comprar($this->proveedor(['nombre' => 'Solo Contado', 'da_credito' => false]), 'credito')
            ->assertSessionHasErrors('tipo_pago');

        $this->actingAs($this->encargado)->post(route('compras.store'), [
            'producto_id' => $this->producto->id, 'cantidad' => 1, 'precio_compra' => 1, 'tipo_pago' => 'credito', 'proveedor' => 'Alguien',
        ])->assertSessionHasErrors('proveedor_id');

        $this->assertSame(0, Compra::count());
        $this->assertEquals(0, $this->producto->fresh()->stock);
    }

    // ---------- Pagos y deuda ----------

    public function test_los_pagos_bajan_la_deuda_y_no_pueden_pasarla(): void
    {
        $proveedor = $this->proveedor();
        $this->comprar($proveedor, 'credito'); // debe $20

        $this->pagar($proveedor, ['monto' => 25])->assertSessionHasErrors('error');
        $this->pagar($proveedor, ['monto' => 15])->assertSessionHasNoErrors();

        $deuda = $proveedor->estadoDeuda();
        $this->assertEquals(5, $deuda['saldo']);
        $this->assertEquals(15, $deuda['pagado']);
        $this->assertNull(PagoProveedor::first()->cierre_caja_id);
    }

    public function test_un_pago_en_bolivares_se_convierte_con_la_tasa(): void
    {
        TasaCambio::create(['fecha' => now()->toDateString(), 'tasa' => 100]);
        $proveedor = $this->proveedor();
        $this->comprar($proveedor, 'credito'); // $20

        $this->pagar($proveedor, ['monto' => 1000, 'moneda' => 'VES'])->assertSessionHasNoErrors();

        $this->assertEquals(10, PagoProveedor::first()->monto);
        $this->assertEquals(10, $proveedor->estadoDeuda()['saldo']);
    }

    public function test_los_pagos_cubren_primero_las_compras_mas_viejas_para_saber_que_vence(): void
    {
        $proveedor = $this->proveedor();
        $base = ['producto_id' => $this->producto->id, 'user_id' => $this->encargado->id, 'proveedor_id' => $proveedor->id,
            'cantidad' => 1, 'precio_compra' => 10, 'total' => 10, 'tipo_pago' => 'credito'];
        Compra::create($base + ['fecha' => now()->subDays(20), 'vence_el' => today()->subDays(5)]);
        Compra::create($base + ['fecha' => now()->subDays(2), 'vence_el' => today()->addDays(13)]);

        $this->assertTrue($proveedor->estadoDeuda()['vencido']);

        $this->pagar($proveedor, ['monto' => 10]);

        $deuda = $proveedor->estadoDeuda();
        $this->assertFalse($deuda['vencido']);
        $this->assertSame(today()->addDays(13)->toDateString(), $deuda['vence_el']->toDateString());
    }

    public function test_un_pago_con_efectivo_de_caja_se_resta_del_cuadre(): void
    {
        $caja = CierreCaja::create(['user_id' => $this->encargado->id, 'fecha' => now()->toDateString(), 'monto_apertura' => 30, 'estado' => 'ABIERTA']);
        $proveedor = $this->proveedor();
        $this->comprar($proveedor, 'credito', 25); // $50

        $this->pagar($proveedor, ['monto' => 40, 'origen' => 'caja'])->assertSessionHasErrors('error'); // solo hay $30
        $this->pagar($proveedor, ['monto' => 12, 'origen' => 'caja'])->assertSessionHasNoErrors();

        $cajas = app(CajaService::class);
        $resumen = $cajas->resumen(now());
        $this->assertEquals(12, $resumen['pagos_usd']);
        $this->assertEquals(18, $cajas->esperado($caja, $resumen)['usd']);

        $this->actingAs($this->encargado)->put(route('caja.cerrar', $caja), ['monto_efectivo_real' => 18, 'monto_efectivo_real_ves' => 0])
            ->assertSessionHasNoErrors();
        $this->assertEquals(0, $caja->fresh()->diferencia);
        $this->assertEquals(12, $caja->fresh()->monto_gastos);
    }

    public function test_sin_caja_abierta_no_se_paga_con_efectivo_de_caja(): void
    {
        $proveedor = $this->proveedor();
        $this->comprar($proveedor, 'credito');

        $this->pagar($proveedor, ['origen' => 'caja'])->assertSessionHasErrors('error');
        $this->assertSame(0, PagoProveedor::count());
    }

    public function test_el_obrero_no_registra_pagos_a_proveedores(): void
    {
        $proveedor = $this->proveedor();
        $obrero = User::factory()->create(['rol' => 'Obrero']);

        $this->actingAs($obrero)->post(route('proveedores.pagos.store', $proveedor), ['monto' => 1, 'moneda' => 'USD', 'origen' => 'otro'])
            ->assertForbidden();
    }

    // ---------- Lo que debemos ----------

    public function test_la_pantalla_de_deudas_muestra_solo_a_quien_se_le_debe(): void
    {
        $conDeuda = $this->proveedor();
        $pagado = $this->proveedor(['nombre' => 'Ya Pagado']);
        $this->comprar($conDeuda, 'credito');
        $this->comprar($pagado, 'credito', 1, 3);
        $this->pagar($pagado, ['monto' => 3]);

        $this->actingAs($this->encargado)->get(route('proveedores.deudas'))
            ->assertOk()
            ->assertSee('Agro Andes')
            ->assertDontSee('Ya Pagado')
            ->assertSee('$20.00');

        $this->actingAs($this->encargado)->get(route('proveedores.deudas.pdf'))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }
}
