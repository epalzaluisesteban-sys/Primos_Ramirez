<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\CierreCaja;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Arreglos de la revisión "como dueño sin experiencia":
 * - si una venta rebota, el formulario conserva los productos, el fiado y el cliente;
 * - la ganancia no cuenta como 100 % lo vendido de productos sin compras registradas.
 */
class UsabilidadVentasTest extends TestCase
{
    use RefreshDatabase;

    private User $dueno;

    protected function setUp(): void
    {
        parent::setUp();
        CierreCaja::olvidarEstados();
        Http::fake();
        $this->dueno = User::factory()->create(['rol' => 'Dueno']);

        // Las ventas solo se registran con la caja del día abierta
        CierreCaja::create(['user_id' => $this->dueno->id, 'fecha' => now()->toDateString(), 'monto_apertura' => 0, 'estado' => 'ABIERTA']);
    }

    private function producto(string $nombre, float $precio, float $stock = 50): Producto
    {
        $categoria = Categoria::firstOrCreate(['nombre' => 'Verduras']);

        return Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => $nombre,
            'unidad_medida' => 'kg',
            'precio' => $precio,
            'stock' => $stock,
            'stock_minimo' => 1,
        ]);
    }

    public function test_si_la_venta_rebota_por_el_limite_de_credito_no_se_pierde_el_carrito(): void
    {
        $papa = $this->producto('Papa', 1.20);
        $manzana = $this->producto('Manzana', 3.00);
        $cliente = Cliente::create(['nombre' => 'Juan Pérez', 'limite_credito' => 5]);

        $respuesta = $this->actingAs($this->dueno)
            ->from(route('ventas.create'))
            ->post(route('ventas.store'), [
                'productos' => [
                    ['id' => $papa->id, 'cantidad' => 2.5],
                    ['id' => $manzana->id, 'cantidad' => 1],
                ],
                'tipo_pago' => 'credito',
                'cliente_id' => $cliente->id,
            ]);

        $respuesta->assertRedirect(route('ventas.create'));
        $respuesta->assertSessionHasErrors('error');
        $respuesta->assertSessionHasInput('cliente_id', $cliente->id);
        $this->assertDatabaseCount('ventas', 0);

        // Al volver al formulario siguen las dos filas, el fiado y el cliente elegido
        $html = $this->get(route('ventas.create'))->assertOk()->getContent();
        $this->assertStringContainsString('name="productos[0][cantidad]" min="0.001" step="0.001" inputmode="decimal" data-entero="0" value="2.5"', $html);
        $this->assertMatchesRegularExpression('/<option value="' . $papa->id . '" data-precio="[^"]*"[^>]* selected/', $html);
        $this->assertMatchesRegularExpression('/<option value="' . $manzana->id . '" data-precio="[^"]*"[^>]* selected/', $html);
        $this->assertMatchesRegularExpression('/value="credito" checked/', $html);
        $this->assertMatchesRegularExpression('/<option value="' . $cliente->id . '" selected/', $html);
    }

    public function test_si_falta_stock_tambien_se_conserva_el_carrito(): void
    {
        $papa = $this->producto('Papa', 1.20, 1);

        $this->actingAs($this->dueno)
            ->from(route('ventas.create'))
            ->post(route('ventas.store'), [
                'productos' => [['id' => $papa->id, 'cantidad' => 3]],
                'tipo_pago' => 'contado',
                'moneda_pago' => 'USD',
            ])
            ->assertSessionHasErrors('error')
            ->assertSessionHasInput('productos');
    }

    public function test_la_ganancia_no_cuenta_lo_vendido_sin_costo_conocido(): void
    {
        $papa = $this->producto('Papa', 2.00);       // con compras: costo $1
        $cambur = $this->producto('Cambur', 1.50);   // sin compras: costo desconocido

        Compra::create([
            'producto_id' => $papa->id,
            'user_id' => $this->dueno->id,
            'cantidad' => 10,
            'precio_compra' => 1.00,
            'total' => 10,
            'fecha' => now(),
        ]);

        $this->actingAs($this->dueno)->post(route('ventas.store'), [
            'productos' => [
                ['id' => $papa->id, 'cantidad' => 5],     // ingreso $10, costo $5
                ['id' => $cambur->id, 'cantidad' => 4],   // ingreso $6, costo ?
            ],
            'tipo_pago' => 'contado',
            'moneda_pago' => 'USD',
        ])->assertSessionHasNoErrors();

        $csv = $this->get(route('reportes.ganancias.excel'))->streamedContent();

        // Totales: ingreso $16, costo $5, utilidad $5 sobre $10 con costo conocido = 50 %
        $this->assertMatchesRegularExpression('/TOTAL[;,]"?"?[;,]"?16\.00"?[;,]"?5\.00"?[;,]"?5\.00"?[;,]"?50\.0"?/', $csv);
        // El cambur no aparece con 100 % de ganancia
        $this->assertMatchesRegularExpression('/Cambur[;,]"?4(\.0+)?"?[;,]"?6\.00"?[;,]"?N\/D"?[;,]"?N\/D"?[;,]"?N\/D"?/', $csv);
    }
}
