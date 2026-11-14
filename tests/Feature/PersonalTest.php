<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\CierreCaja;
use App\Models\Compra;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Pantalla de Personal: quitar/devolver acceso sin borrar el historial
 * y que la tabla ofrezca el botón correcto según tenga o no movimientos.
 */
class PersonalTest extends TestCase
{
    use RefreshDatabase;

    private User $dueno;

    protected function setUp(): void
    {
        parent::setUp();
        CierreCaja::olvidarEstados();
        Http::fake();
        $this->dueno = User::factory()->create(['rol' => 'Dueno']);
    }

    private function darMovimiento(User $usuario): void
    {
        $categoria = Categoria::firstOrCreate(['nombre' => 'Frutas']);
        $producto = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Cambur',
            'unidad_medida' => 'kg',
            'precio' => 2,
            'stock' => 10,
            'stock_minimo' => 1,
        ]);
        Compra::create([
            'producto_id' => $producto->id,
            'user_id' => $usuario->id,
            'cantidad' => 5,
            'precio_compra' => 1,
            'total' => 5,
            'fecha' => now(),
        ]);
    }

    public function test_el_dueno_quita_y_devuelve_el_acceso_sin_borrar_al_usuario(): void
    {
        $obrero = User::factory()->create(['rol' => 'Obrero', 'activo' => true]);
        $this->darMovimiento($obrero);

        $this->actingAs($this->dueno)
            ->patch(route('usuarios.activo', $obrero))
            ->assertSessionHasNoErrors();
        $this->assertFalse($obrero->fresh()->activo);

        $this->actingAs($this->dueno)
            ->patch(route('usuarios.activo', $obrero))
            ->assertSessionHasNoErrors();
        $this->assertTrue($obrero->fresh()->activo);

        $this->assertSame(1, Compra::where('user_id', $obrero->id)->count());
    }

    public function test_nadie_se_quita_el_acceso_a_si_mismo(): void
    {
        $this->actingAs($this->dueno)
            ->patch(route('usuarios.activo', $this->dueno))
            ->assertSessionHasErrors('error');

        $this->assertTrue($this->dueno->fresh()->activo);
    }

    public function test_solo_el_dueno_puede_quitar_el_acceso(): void
    {
        $encargado = User::factory()->create(['rol' => 'Encargado']);
        $obrero = User::factory()->create(['rol' => 'Obrero', 'activo' => true]);

        $this->actingAs($encargado)
            ->patch(route('usuarios.activo', $obrero))
            ->assertForbidden();

        $this->assertTrue($obrero->fresh()->activo);
    }

    public function test_la_tabla_ofrece_quitar_acceso_o_eliminar_segun_el_historial(): void
    {
        $conHistorial = User::factory()->create(['rol' => 'Obrero', 'activo' => true]);
        $sinHistorial = User::factory()->create(['rol' => 'Obrero', 'activo' => true]);
        $this->darMovimiento($conHistorial);

        $respuesta = $this->actingAs($this->dueno)->get(route('usuarios.index'));
        $accion = fn (string $ruta, User $u) => 'action="' . route($ruta, $u) . '"';

        $respuesta->assertOk()
            ->assertSee($accion('usuarios.activo', $conHistorial), false)
            ->assertDontSee($accion('usuarios.destroy', $conHistorial), false)
            ->assertSee($accion('usuarios.destroy', $sinHistorial), false)
            ->assertDontSee($accion('usuarios.activo', $sinHistorial), false);
    }

    public function test_el_formulario_muestra_los_permisos_del_rol(): void
    {
        $obrero = User::factory()->create(['rol' => 'Obrero']);

        $this->actingAs($this->dueno)
            ->get(route('usuarios.edit', $obrero))
            ->assertOk()
            ->assertSee('Abrir y cerrar caja');
    }

    public function test_el_rol_dueno_se_muestra_con_enie(): void
    {
        $respuesta = $this->actingAs($this->dueno)->get(route('profile.edit'));

        $respuesta->assertOk()->assertSee('Dueño');
        $this->assertStringNotContainsString('>Dueno<', $respuesta->getContent());
        $this->assertStringNotContainsString('· Dueno', $respuesta->getContent());
        $this->assertSame('Dueno', $this->dueno->fresh()->rol);
    }
}
