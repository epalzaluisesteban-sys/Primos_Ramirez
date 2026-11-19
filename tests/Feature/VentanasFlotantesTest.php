<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\CierreCaja;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Fichas y formularios en ventana flotante:
 * 1. Tocar una fila de clientes, proveedores o caja pide su ficha por fetch (sin el layout).
 * 2. "Nuevo…" abre el formulario en una ventana; si el servidor lo devuelve con errores, la ventana se reabre sola.
 * 3. "Editar" pide el formulario por fetch; con errores se reabre con lo escrito sin llenar el de "Nuevo…".
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

    public function test_editar_se_pide_por_fetch_y_abierto_directo_sigue_siendo_pagina(): void
    {
        $proveedor = Proveedor::create(['nombre' => 'Mercado Mayorista', 'activo' => true]);
        $categoria = Categoria::create(['nombre' => 'Frutas']);
        $producto = Producto::create(['nombre' => 'Cambur', 'categoria_id' => $categoria->id, 'precio' => 1, 'stock' => 0, 'stock_minimo' => 1, 'unidad_medida' => 'kg']);
        $cliente = Cliente::create(['nombre' => 'Rosa Pérez']);

        $this->actingAs($this->encargado)->get(route('proveedores.index'))
            ->assertSee('href="' . route('proveedores.edit', $proveedor) . '" data-editar', false);

        foreach ([
            [route('proveedores.edit', $proveedor), route('proveedores.update', $proveedor)],
            [route('productos.edit', $producto), route('productos.update', $producto)],
            [route('clientes.edit', $cliente), route('clientes.update', $cliente)],
            [route('categorias.edit', $categoria), route('categorias.update', $categoria)],
        ] as [$editar, $actualizar]) {
            $this->actingAs($this->encargado)->get($editar, $this->ajax)
                ->assertOk()
                ->assertSee('action="' . $actualizar . '"', false)
                ->assertSee('name="_editar_en" value="' . $editar . '"', false)
                ->assertSee('data-cerrar-ventana', false)
                ->assertDontSee('<html', false);

            $this->actingAs($this->encargado)->get($editar)
                ->assertOk()
                ->assertSee('<html', false)
                ->assertDontSee('name="_editar_en"', false);
        }
    }

    public function test_si_editar_vuelve_con_errores_la_ventana_se_reabre_sin_ensuciar_nuevo(): void
    {
        $proveedor = Proveedor::create(['nombre' => 'Mercado Mayorista', 'activo' => true]);
        $editar = route('proveedores.edit', $proveedor);

        $this->actingAs($this->encargado)
            ->from(route('proveedores.index'))
            ->put(route('proveedores.update', $proveedor), ['_editar_en' => $editar, 'nombre' => '', 'contacto' => 'Escrito al editar'])
            ->assertRedirect(route('proveedores.index'))
            ->assertSessionHasErrors('nombre');

        // El listado pide reabrir la edición y el formulario de "Nuevo proveedor" queda limpio
        $listado = $this->actingAs($this->encargado)->get(route('proveedores.index'))->getContent();
        $this->assertStringContainsString('cargarEnVentana(' . json_encode($editar) . ', ', $listado);
        $this->assertStringNotContainsString('Escrito al editar', $listado);
        $this->assertDoesNotMatchRegularExpression('/id="ventana-proveedor" data-ventana\s+data-abrir-al-cargar/', $listado);

        // La ventana recibe lo escrito y el error
        $this->actingAs($this->encargado)->get($editar, $this->ajax)
            ->assertOk()
            ->assertSee('value="Escrito al editar"', false)
            ->assertSee('class="error-campo"', false);

        // Y después todo vuelve a la normalidad
        $this->actingAs($this->encargado)->get($editar, $this->ajax)
            ->assertDontSee('Escrito al editar')
            ->assertSee('value="Mercado Mayorista"', false);
    }

    public function test_la_fila_de_la_categoria_abre_su_ficha(): void
    {
        $categoria = Categoria::create(['nombre' => 'Frutas', 'descripcion' => 'De temporada']);
        Producto::create(['nombre' => 'Cambur', 'categoria_id' => $categoria->id, 'precio' => 1, 'stock' => 3, 'stock_minimo' => 1, 'unidad_medida' => 'kg']);
        $proveedor = Proveedor::create(['nombre' => 'Mercado Mayorista', 'activo' => true]);
        $proveedor->categorias()->attach($categoria);

        $this->actingAs($this->encargado)->get(route('categorias.index'))
            ->assertSee('data-ficha="' . route('categorias.show', $categoria) . '"', false);

        $this->actingAs($this->encargado)->get(route('categorias.show', $categoria), $this->ajax)
            ->assertOk()
            ->assertSee('De temporada')
            ->assertSee('Mercado Mayorista')
            ->assertSee('Cambur')
            ->assertSee('Productos (1)')
            ->assertDontSee('<html', false);

        $this->actingAs($this->encargado)->get(route('categorias.show', $categoria))
            ->assertOk()
            ->assertSee('<html', false);
    }

    public function test_la_fila_del_usuario_abre_su_ficha_y_se_edita_en_ventana(): void
    {
        $dueno = User::factory()->create(['rol' => 'Dueno']);

        $this->actingAs($dueno)->get(route('usuarios.index'))
            ->assertSee('data-ficha="' . route('usuarios.show', $this->encargado) . '"', false)
            ->assertSee('href="' . route('usuarios.edit', $this->encargado->id) . '" data-editar', false);

        $this->actingAs($dueno)->get(route('usuarios.show', $this->encargado), $this->ajax)
            ->assertOk()
            ->assertSee($this->encargado->email)
            ->assertSee('Ventas este mes')
            ->assertDontSee('<html', false);

        $this->actingAs($dueno)->get(route('usuarios.edit', $this->encargado), $this->ajax)
            ->assertOk()
            ->assertSee('action="' . route('usuarios.update', $this->encargado) . '"', false)
            ->assertSee('name="_editar_en"', false)
            ->assertDontSee('<html', false);

        // El encargado no ve fichas del personal
        $this->actingAs($this->encargado)->get(route('usuarios.show', $dueno), $this->ajax)->assertForbidden();
    }

    public function test_la_fila_de_la_compra_abre_su_detalle(): void
    {
        $categoria = Categoria::create(['nombre' => 'Frutas']);
        $producto = Producto::create(['nombre' => 'Cambur', 'categoria_id' => $categoria->id, 'precio' => 1, 'stock' => 0, 'stock_minimo' => 1, 'unidad_medida' => 'kg']);
        $proveedor = Proveedor::create(['nombre' => 'Mercado Mayorista', 'activo' => true, 'da_credito' => true, 'dias_credito' => 15]);
        $compra = Compra::create([
            'producto_id' => $producto->id, 'user_id' => $this->encargado->id, 'proveedor_id' => $proveedor->id,
            'cantidad' => 12.5, 'precio_compra' => 0.8, 'total' => 10, 'tipo_pago' => 'credito',
            'vence_el' => now()->addDays(15), 'fecha' => now(),
        ]);

        $this->actingAs($this->encargado)->get(route('compras.index'))
            ->assertSee('data-ficha="' . route('compras.show', $compra) . '"', false);

        $this->actingAs($this->encargado)->get(route('compras.show', $compra), $this->ajax)
            ->assertOk()
            ->assertSee('Cambur')
            ->assertSee('+12.5')
            ->assertSee('A crédito')
            ->assertSee(route('proveedores.show', $proveedor), false)
            ->assertDontSee('<html', false);
    }

    public function test_perdidas_gastos_y_kardex_abren_su_detalle(): void
    {
        $categoria = Categoria::create(['nombre' => 'Frutas']);
        $producto = Producto::create(['nombre' => 'Cambur', 'categoria_id' => $categoria->id, 'precio' => 2, 'stock' => 10, 'stock_minimo' => 1, 'unidad_medida' => 'kg']);
        $merma = \App\Models\Merma::create(['producto_id' => $producto->id, 'user_id' => $this->encargado->id, 'cantidad' => 1.5, 'motivo' => 'vencido', 'observacion' => 'Se maduró de más', 'fecha' => now()]);
        $caja = CierreCaja::create(['user_id' => $this->encargado->id, 'fecha' => now()->toDateString(), 'estado' => 'ABIERTA', 'monto_apertura' => 20, 'monto_apertura_ves' => 0]);
        $gasto = \App\Models\Gasto::create([
            'cierre_caja_id' => $caja->id, 'user_id' => $this->encargado->id, 'concepto' => 'Flete del mercado', 'categoria' => 'transporte',
            'monto' => 5, 'moneda' => 'USD', 'monto_moneda' => 5, 'fecha' => now(),
        ]);

        $this->actingAs($this->encargado)->get(route('mermas.index'))
            ->assertSee('data-ficha="' . route('mermas.show', $merma) . '"', false);
        $this->actingAs($this->encargado)->get(route('mermas.show', $merma), $this->ajax)
            ->assertOk()->assertSee('Se maduró de más')->assertSee('$3.00')->assertDontSee('<html', false);

        $this->actingAs($this->encargado)->get(route('gastos.index'))
            ->assertSee('data-ficha="' . route('gastos.show', $gasto) . '"', false);
        $this->actingAs($this->encargado)->get(route('gastos.show', $gasto), $this->ajax)
            ->assertOk()->assertSee('Flete del mercado')->assertSee(route('caja.show', $caja), false)->assertDontSee('<html', false);

        // En el kardex, la pérdida abre su ficha
        $this->actingAs($this->encargado)->get(route('productos.kardex', $producto))
            ->assertSee('data-ficha="' . route('mermas.show', $merma) . '"', false);
    }

    public function test_la_fila_de_la_papelera_abre_el_detalle_de_la_venta(): void
    {
        $venta = \App\Models\Venta::create([
            'user_id' => $this->encargado->id, 'fecha' => now(), 'total' => 2, 'tipo_pago' => 'contado',
            'anulada' => true, 'anulada_at' => now(), 'motivo_anulacion' => 'Se cobró dos veces',
        ]);

        $this->actingAs($this->encargado)->get(route('ventas.papelera'))
            ->assertOk()
            ->assertSee('data-ficha="' . route('ventas.show', $venta) . '"', false);
    }

    public function test_desde_productos_la_compra_abre_con_el_producto_elegido(): void
    {
        $abierta = '/id="ventana-compra" data-ventana\s+data-abrir-al-cargar/';
        $this->assertDoesNotMatchRegularExpression($abierta, $this->actingAs($this->encargado)->get(route('compras.index'))->getContent());
        $this->assertMatchesRegularExpression($abierta, $this->actingAs($this->encargado)->get(route('compras.index', ['producto' => 1]))->getContent());
    }
}
