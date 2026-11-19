<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\CierreCaja;
use App\Models\Merma;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Todos los campos validados en el servidor, con mensajes en español que dicen qué corregir.
 */
class ValidacionFormulariosTest extends TestCase
{
    use RefreshDatabase;

    private User $dueno;
    private Categoria $frutas;

    protected function setUp(): void
    {
        parent::setUp();
        CierreCaja::olvidarEstados();
        Http::fake();
        $this->dueno = User::factory()->create(['rol' => 'Dueno', 'email' => 'dueno@prueba.test']);
        $this->frutas = Categoria::create(['nombre' => 'Frutas']);
    }

    private function producto(string $nombre = 'Mango'): Producto
    {
        return Producto::create([
            'categoria_id' => $this->frutas->id, 'nombre' => $nombre, 'unidad_medida' => 'kg',
            'precio' => 2, 'stock' => 20, 'stock_minimo' => 1,
        ]);
    }

    private function datosProducto(array $cambios = []): array
    {
        return $cambios + [
            'nombre' => 'Lechosa', 'categoria_id' => $this->frutas->id, 'unidad_medida' => 'kg',
            'precio' => 3, 'stock' => 0, 'stock_minimo' => 1,
        ];
    }

    // ---------- Productos ----------

    public function test_el_precio_de_venta_no_puede_ser_cero(): void
    {
        $this->actingAs($this->dueno)->post(route('productos.store'), $this->datosProducto(['precio' => 0]))
            ->assertSessionHasErrors(['precio' => 'El precio de venta tiene que ser mayor que $0.']);
        $this->assertSame(0, Producto::count());
    }

    public function test_un_producto_nuevo_no_puede_registrarse_vencido_pero_al_editar_se_conserva_la_fecha(): void
    {
        $this->actingAs($this->dueno)->post(route('productos.store'), $this->datosProducto(['fecha_vencimiento' => now()->subDay()->toDateString()]))
            ->assertSessionHasErrors('fecha_vencimiento');

        $producto = $this->producto();
        $producto->update(['fecha_vencimiento' => now()->subDays(3)]);

        $this->actingAs($this->dueno)->put(route('productos.update', $producto), $this->datosProducto([
            'nombre' => 'Mango', 'stock' => 20, 'fecha_vencimiento' => now()->subDays(3)->toDateString(), 'activo' => 1,
        ]))->assertSessionHasNoErrors();
    }

    public function test_la_existencia_maxima_no_puede_ser_menor_que_la_minima(): void
    {
        $this->actingAs($this->dueno)->post(route('productos.store'), $this->datosProducto(['stock_minimo' => 10, 'stock_maximo' => 5]))
            ->assertSessionHasErrors(['stock_maximo' => 'La existencia máxima no puede ser menor que la mínima.']);
    }

    // ---------- Ventas ----------

    public function test_una_venta_no_lleva_el_mismo_producto_en_dos_lineas(): void
    {
        CierreCaja::create(['user_id' => $this->dueno->id, 'fecha' => now()->toDateString(), 'monto_apertura' => 0, 'estado' => 'ABIERTA']);
        $mango = $this->producto();

        $this->actingAs($this->dueno)->post(route('ventas.store'), [
            'productos' => [['id' => $mango->id, 'cantidad' => 1], ['id' => $mango->id, 'cantidad' => 2]],
            'tipo_pago' => 'contado',
        ])->assertSessionHasErrors(['productos.1.id' => 'Elegiste el mismo producto en dos líneas: deja una sola y suma la cantidad.']);

        $this->assertSame(0, Venta::count());
        $this->assertEquals(20, $mango->fresh()->stock);
    }

    // ---------- Pérdidas ----------

    public function test_una_perdida_por_otro_motivo_necesita_explicacion(): void
    {
        $mango = $this->producto();

        $this->actingAs($this->dueno)->post(route('mermas.store'), ['producto_id' => $mango->id, 'cantidad' => 1, 'motivo' => 'otro'])
            ->assertSessionHasErrors(['observacion' => 'Con el motivo "Otro", explica en la observación qué pasó.']);

        $this->actingAs($this->dueno)->post(route('mermas.store'), ['producto_id' => $mango->id, 'cantidad' => 30, 'motivo' => 'danado'])
            ->assertSessionHasErrors('cantidad');

        $this->assertSame(0, Merma::count());
        $this->assertEquals(20, $mango->fresh()->stock);
    }

    // ---------- Usuarios ----------

    public function test_al_crear_un_usuario_la_contrasena_se_confirma_y_el_correo_queda_en_minusculas(): void
    {
        $datos = ['name' => 'Carla', 'email' => '  Carla@Correo.COM ', 'password' => 'secreta123', 'rol' => 'Obrero'];

        $this->actingAs($this->dueno)->post(route('usuarios.store'), $datos + ['password_confirmation' => 'otra-cosa'])
            ->assertSessionHasErrors(['password' => 'Las dos contraseñas no coinciden. Escríbelas otra vez.']);

        $this->actingAs($this->dueno)->post(route('usuarios.store'), $datos + ['password_confirmation' => 'secreta123'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['email' => 'carla@correo.com']);

        // El mismo correo con otras mayúsculas ya existe
        $this->actingAs($this->dueno)->post(route('usuarios.store'), ['email' => 'CARLA@correo.com', 'name' => 'Otra'] + $datos + ['password_confirmation' => 'secreta123'])
            ->assertSessionHasErrors('email');
    }

    public function test_se_inicia_sesion_aunque_el_correo_se_escriba_con_mayusculas(): void
    {
        auth()->logout();

        $this->post(route('login.post'), ['email' => 'DUENO@Prueba.test', 'password' => 'password'])
            ->assertRedirect('/');
        $this->assertAuthenticatedAs($this->dueno);
    }

    // ---------- Filtros y mensajes ----------

    public function test_una_fecha_invalida_en_el_historial_no_rompe_la_pantalla(): void
    {
        $this->actingAs($this->dueno)->get(route('historial.index', ['desde' => 'no-es-fecha']))
            ->assertRedirect()
            ->assertSessionHasErrors('desde');

        $this->actingAs($this->dueno)->get(route('historial.index', ['desde' => '2026-11-10', 'hasta' => '2026-11-01']))
            ->assertSessionHasErrors(['hasta' => 'La fecha "Hasta" no puede ser anterior a "Desde".']);
    }

    public function test_los_mensajes_salen_en_espanol(): void
    {
        $mango = $this->producto();

        $this->actingAs($this->dueno)->post(route('mermas.store'), ['producto_id' => $mango->id, 'cantidad' => 'mucho', 'motivo' => 'x'])
            ->assertSessionHasErrors([
                'cantidad' => 'Cantidad debe ser un número.',
                'motivo' => 'Motivo seleccionado no es válido.',
            ]);
    }
}
