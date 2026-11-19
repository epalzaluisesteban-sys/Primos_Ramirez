<?php

namespace Tests\Feature;

use App\Models\Abono;
use App\Models\Categoria;
use App\Models\CierreCaja;
use App\Models\Cliente;
use App\Models\Gasto;
use App\Models\Producto;
use App\Models\TasaCambio;
use App\Models\User;
use App\Models\Venta;
use App\Services\CajaService;
use App\Services\TasaCambioService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * 1. Una sola caja por día.
 * 2. Con la caja de un día anterior abierta no se abre la de hoy ni se vende, cobra o gasta: primero se cierra.
 * 3. Los gastos del día se restan del efectivo esperado, por moneda.
 * 4. Reporte del día (PDF y ticket) sin ventas anuladas.
 */
class CajaTest extends TestCase
{
    use RefreshDatabase;

    private User $obrero;
    private User $encargado;

    protected function setUp(): void
    {
        parent::setUp();
        CierreCaja::olvidarEstados();
        TasaCambioService::olvidar();
        Http::fake();
        $this->obrero = User::factory()->create(['rol' => 'Obrero', 'name' => 'Pedro']);
        $this->encargado = User::factory()->create(['rol' => 'Encargado', 'name' => 'Carmen']);
    }

    private function caja(string $fecha, string $estado = 'ABIERTA', array $datos = []): CierreCaja
    {
        return CierreCaja::create($datos + [
            'user_id' => $this->obrero->id, 'fecha' => $fecha, 'monto_apertura' => 20, 'monto_apertura_ves' => 0, 'estado' => $estado,
        ]);
    }

    private function producto(): Producto
    {
        return Producto::create([
            'categoria_id' => Categoria::firstOrCreate(['nombre' => 'Frutas'])->id,
            'nombre' => 'Cambur', 'unidad_medida' => 'kg', 'precio' => 2, 'stock' => 50, 'stock_minimo' => 1,
        ]);
    }

    private function gasto(array $datos = [])
    {
        return $this->actingAs($this->obrero)->post(route('gastos.store'), $datos + [
            'concepto' => 'Flete al mercado', 'categoria' => 'transporte', 'moneda' => 'USD', 'monto' => 5,
        ]);
    }

    // ---------- Una caja por día ----------

    public function test_no_se_abre_una_segunda_caja_el_mismo_dia(): void
    {
        $abrir = fn () => $this->actingAs($this->obrero)->post(route('caja.abrir'), ['monto_apertura' => 10, 'monto_apertura_ves' => 0]);

        $abrir()->assertSessionHasNoErrors();
        CierreCaja::olvidarEstados();
        $abrir()->assertSessionHasErrors('error');

        $this->assertSame(1, CierreCaja::count());
    }

    public function test_la_base_de_datos_rechaza_dos_cajas_con_la_misma_fecha(): void
    {
        $this->caja(now()->toDateString());

        $this->expectException(UniqueConstraintViolationException::class);
        $this->caja(now()->toDateString(), 'CERRADA');
    }

    // ---------- Caja de un día anterior sin cerrar ----------

    public function test_con_la_caja_de_ayer_abierta_no_se_abre_la_de_hoy(): void
    {
        $this->caja(now()->subDay()->toDateString());

        $this->actingAs($this->obrero)->post(route('caja.abrir'), ['monto_apertura' => 10, 'monto_apertura_ves' => 0])
            ->assertSessionHasErrors(['error' => 'La caja del ' . now()->subDay()->format('d/m/Y') . ' quedó abierta. Ciérrala en "Caja" (cuenta el efectivo de ese día) y después abre la de hoy.']);

        $this->assertFalse(CierreCaja::estaAbierta(now()));
    }

    public function test_con_la_caja_de_ayer_abierta_no_se_vende_aunque_la_de_hoy_este_abierta(): void
    {
        // Así quedaban los datos antes del arreglo: la de ayer sin cerrar y la de hoy abierta
        $this->caja(now()->subDay()->toDateString());
        $this->caja(now()->toDateString());
        $producto = $this->producto();

        $this->actingAs($this->obrero)->post(route('ventas.store'), [
            'productos' => [['id' => $producto->id, 'cantidad' => 1]], 'tipo_pago' => 'contado',
        ])->assertRedirect(route('caja.index'))->assertSessionHasErrors('error');

        $this->actingAs($this->obrero)->get(route('ventas.create'))->assertRedirect(route('caja.index'));

        $this->assertSame(0, Venta::count());
        $this->assertSame(50.0, $producto->fresh()->stock);
    }

    public function test_con_la_caja_de_ayer_abierta_no_se_cobra_abono_ni_gasto(): void
    {
        $this->caja(now()->subDay()->toDateString());
        $this->caja(now()->toDateString());
        $cliente = Cliente::create(['nombre' => 'Ana']);
        Venta::create(['user_id' => $this->obrero->id, 'cliente_id' => $cliente->id, 'fecha' => now()->subDays(3), 'total' => 10, 'tipo_pago' => 'credito']);

        $this->actingAs($this->obrero)->post(route('clientes.abonos.store', $cliente), ['monto' => 5])->assertSessionHasErrors('error');
        $this->gasto()->assertSessionHasErrors('error');

        $this->assertSame(0, Abono::count());
        $this->assertSame(0, Gasto::count());
    }

    public function test_la_pantalla_de_caja_ofrece_cerrar_la_caja_pendiente_y_despues_abrir_la_de_hoy(): void
    {
        $ayer = $this->caja(now()->subDay()->toDateString());

        $this->actingAs($this->obrero)->get(route('caja.index'))
            ->assertOk()
            ->assertSee('Caja pendiente del ' . $ayer->fechaCorta())
            ->assertSee(route('caja.cerrar', $ayer), false)
            ->assertDontSee(route('caja.abrir'), false);

        $this->actingAs($this->obrero)->put(route('caja.cerrar', $ayer), ['monto_efectivo_real' => 20, 'monto_efectivo_real_ves' => 0])
            ->assertSessionHasNoErrors();
        CierreCaja::olvidarEstados();

        $this->assertSame('CERRADA', $ayer->fresh()->estado);
        $this->assertSame($this->obrero->id, $ayer->fresh()->cerrada_por);
        $this->actingAs($this->obrero)->get(route('caja.index'))->assertSee(route('caja.abrir'), false);
        $this->actingAs($this->obrero)->post(route('caja.abrir'), ['monto_apertura' => 10, 'monto_apertura_ves' => 0])
            ->assertSessionHasNoErrors();
    }

    public function test_con_varias_cajas_pendientes_se_cierra_primero_la_mas_vieja(): void
    {
        $this->caja(now()->subDays(2)->toDateString());
        $this->caja(now()->subDays(5)->toDateString());

        $this->assertSame(now()->subDays(5)->toDateString(), CierreCaja::pendienteAnterior()->fecha);
    }

    // ---------- Gastos ----------

    public function test_el_cierre_resta_los_gastos_por_moneda(): void
    {
        TasaCambio::create(['fecha' => now()->toDateString(), 'tasa' => 100]);
        $caja = $this->caja(now()->toDateString(), 'ABIERTA', ['monto_apertura' => 20, 'monto_apertura_ves' => 1000]);

        $this->gasto(['monto' => 5])->assertSessionHasNoErrors();
        $this->gasto(['moneda' => 'VES', 'monto' => 300, 'categoria' => 'comida', 'concepto' => 'Almuerzo'])->assertSessionHasNoErrors();

        $gastoVes = Gasto::where('moneda', 'VES')->first();
        $this->assertEquals(300, $gastoVes->monto_moneda);
        $this->assertEquals(3, $gastoVes->monto);          // equivalente en USD
        $this->assertSame($caja->id, $gastoVes->cierre_caja_id);

        $this->actingAs($this->obrero)->put(route('caja.cerrar', $caja), ['monto_efectivo_real' => 15, 'monto_efectivo_real_ves' => 700])
            ->assertSessionHasNoErrors();

        $caja->refresh();
        $this->assertEquals(5, $caja->monto_gastos);
        $this->assertEquals(300, $caja->monto_gastos_ves);
        $this->assertEquals(0, $caja->diferencia);      // 20 − 5 = 15
        $this->assertEquals(0, $caja->diferencia_ves);  // 1000 − 300 = 700
    }

    public function test_un_gasto_no_puede_ser_mayor_que_el_efectivo_en_caja(): void
    {
        $this->caja(now()->toDateString(), 'ABIERTA', ['monto_apertura' => 20]);

        $this->gasto(['monto' => 25])->assertSessionHasErrors('error');
        $this->assertSame(0, Gasto::count());
    }

    public function test_el_gasto_valida_sus_campos(): void
    {
        $this->caja(now()->toDateString());

        $this->gasto(['concepto' => '', 'categoria' => 'fiesta', 'moneda' => 'EUR', 'monto' => 0])
            ->assertSessionHasErrors(['concepto', 'categoria', 'moneda', 'monto']);
        $this->assertSame(0, Gasto::count());
    }

    public function test_sin_caja_de_hoy_abierta_no_se_anotan_gastos(): void
    {
        $this->gasto()->assertSessionHasErrors('error');

        $this->caja(now()->toDateString(), 'CERRADA');
        CierreCaja::olvidarEstados();
        $this->gasto()->assertSessionHasErrors('error');

        $this->assertSame(0, Gasto::count());
    }

    public function test_solo_el_encargado_quita_un_gasto_y_solo_con_la_caja_abierta(): void
    {
        $caja = $this->caja(now()->toDateString());
        $this->gasto()->assertSessionHasNoErrors();
        $gasto = Gasto::first();

        $this->actingAs($this->obrero)->delete(route('gastos.destroy', $gasto))->assertForbidden();

        $caja->update(['estado' => 'CERRADA']);
        $this->actingAs($this->encargado)->delete(route('gastos.destroy', $gasto))->assertSessionHasErrors('error');
        $this->assertSame(1, Gasto::count());

        $caja->update(['estado' => 'ABIERTA']);
        $this->actingAs($this->encargado)->delete(route('gastos.destroy', $gasto))->assertSessionHasNoErrors();
        $this->assertSame(0, Gasto::count());
    }

    public function test_los_gastos_tienen_su_propio_registro_con_filtros(): void
    {
        $caja = $this->caja(now()->toDateString(), 'ABIERTA', ['monto_apertura' => 50]);
        $this->gasto(['concepto' => 'Flete al mercado', 'monto' => 5])->assertRedirect(route('gastos.index'));
        $this->gasto(['concepto' => 'Almuerzo del personal', 'categoria' => 'comida', 'monto' => 3])->assertSessionHasNoErrors();
        Gasto::create([
            'cierre_caja_id' => $caja->id, 'user_id' => $this->obrero->id, 'concepto' => 'Flete del mes pasado', 'categoria' => 'transporte',
            'monto' => 7, 'moneda' => 'USD', 'monto_moneda' => 7, 'fecha' => now()->subMonths(2),
        ]);

        // Sin filtro: el mes en curso. El obrero ve el registro y el formulario, pero no la exportación.
        $this->actingAs($this->obrero)->get(route('gastos.index'))
            ->assertOk()
            ->assertSee('Flete al mercado')
            ->assertSee('Almuerzo del personal')
            ->assertDontSee('Flete del mes pasado')
            ->assertSee('Anotar gasto')
            ->assertDontSee(route('gastos.excel'));

        $this->actingAs($this->obrero)->get(route('gastos.index', ['tipo' => 'comida']))
            ->assertSee('Almuerzo del personal')
            ->assertDontSee('Flete al mercado');

        $this->actingAs($this->obrero)->get(route('gastos.excel'))->assertForbidden();

        $csv = $this->actingAs($this->encargado)
            ->get(route('gastos.excel', ['fecha_inicio' => now()->subMonths(3)->toDateString(), 'fecha_fin' => now()->toDateString()]))
            ->streamedContent();
        $this->assertStringContainsString('Flete del mes pasado', $csv);
        $this->assertStringContainsString('Almuerzo del personal', $csv);

        $this->actingAs($this->encargado)->get(route('gastos.pdf'))->assertOk();
    }

    public function test_con_la_caja_cerrada_la_pantalla_de_gastos_no_deja_anotar(): void
    {
        $this->caja(now()->toDateString(), 'CERRADA');

        $this->actingAs($this->obrero)->get(route('gastos.index'))
            ->assertOk()
            ->assertSee('La caja de hoy ya está cerrada')
            ->assertDontSee('name="concepto"', false);
    }

    // ---------- Reabrir y reporte ----------

    public function test_reabrir_la_caja_de_hoy_sigue_funcionando_y_borra_quien_la_cerro(): void
    {
        $caja = $this->caja(now()->toDateString());
        $this->actingAs($this->obrero)->put(route('caja.cerrar', $caja), ['monto_efectivo_real' => 20, 'monto_efectivo_real_ves' => 0]);

        $this->actingAs($this->encargado)->put(route('caja.reabrir', $caja))->assertSessionHasNoErrors();

        $caja->refresh();
        $this->assertSame('ABIERTA', $caja->estado);
        $this->assertNull($caja->cerrada_por);
    }

    public function test_el_reporte_del_dia_no_incluye_ventas_anuladas(): void
    {
        $caja = $this->caja(now()->toDateString());
        $producto = $this->producto();
        $valida = Venta::create(['user_id' => $this->obrero->id, 'fecha' => now(), 'total' => 4, 'tipo_pago' => 'contado', 'moneda_pago' => 'USD']);
        $valida->detalles()->create(['producto_id' => $producto->id, 'cantidad' => 2, 'precio_unitario' => 2, 'subtotal' => 4]);
        $anulada = Venta::create(['user_id' => $this->obrero->id, 'fecha' => now(), 'total' => 6, 'tipo_pago' => 'contado', 'moneda_pago' => 'USD', 'anulada' => true]);
        $anulada->detalles()->create(['producto_id' => $producto->id, 'cantidad' => 3, 'precio_unitario' => 2, 'subtotal' => 6]);

        $reporte = app(CajaService::class)->reporte($caja);

        $this->assertEquals([$valida->id], $reporte['ventas']->pluck('id')->all());
        $this->assertEquals([$anulada->id], $reporte['anuladas']->pluck('id')->all());
        $this->assertEquals(2, $reporte['productos'][0]['cantidad']);
        $this->assertEquals(4, $reporte['totalVendido']);
        $this->assertEquals(24, $reporte['esperado']['usd']);
    }

    public function test_el_reporte_pdf_y_el_ticket_se_generan(): void
    {
        $caja = $this->caja(now()->toDateString(), 'CERRADA', ['monto_efectivo_real' => 20, 'monto_efectivo_real_ves' => 0, 'cerrada_por' => $this->obrero->id, 'cerrada_at' => now()]);

        $this->actingAs($this->obrero)->get(route('caja.reporte', $caja))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        // El ticket también es un PDF (80 mm), abierto en el navegador para imprimirlo
        $this->actingAs($this->obrero)->get(route('caja.ticket', $caja))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $html = view('caja.ticket', app(CajaService::class)->reporte($caja))->render();
        $this->assertStringContainsString('Cierre de caja', $html);
        $this->assertStringContainsString('Cerró: Pedro', $html);
    }
}
