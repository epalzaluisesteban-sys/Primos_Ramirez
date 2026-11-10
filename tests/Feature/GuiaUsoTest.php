<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * La guía "Cómo usar" es de práctica: la abren todos los roles desde el menú del avatar
 * y sus formularios de muestra no pueden enviar nada al servidor.
 */
class GuiaUsoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    public function test_todos_los_roles_abren_la_guia_desde_el_menu(): void
    {
        foreach (['Dueno', 'Encargado', 'Obrero'] as $rol) {
            $usuario = User::factory()->create(['rol' => $rol]);

            $this->actingAs($usuario)->get(route('profile.edit'))
                ->assertOk()
                ->assertSee(route('ayuda.index'), false);

            $this->actingAs($usuario)->get(route('ayuda.index'))
                ->assertOk()
                ->assertSee('Modo práctica: nada de lo que hagas aquí se guarda.');
        }
    }

    public function test_los_campos_de_practica_no_pueden_enviar_datos(): void
    {
        $html = $this->actingAs(User::factory()->create(['rol' => 'Dueno']))
            ->get(route('ayuda.index'))
            ->getContent();

        $guia = Str::between($html, 'id="guia"', '</main>');

        $this->assertStringContainsString('data-campo="cantidad"', $guia);
        $this->assertStringNotContainsString('<form', $guia);
        $this->assertStringNotContainsString('name="', $guia);
        $this->assertStringNotContainsString('type="submit"', $guia);
    }

    public function test_el_obrero_puede_filtrar_los_capitulos_que_le_tocan(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'Obrero']))
            ->get(route('ayuda.index'))
            ->assertSee('Ver solo lo que me toca', false)
            ->assertSee('Tú no lo haces, pero conviene saber cómo funciona.');

        $this->actingAs(User::factory()->create(['rol' => 'Encargado']))
            ->get(route('ayuda.index'))
            ->assertDontSee('Ver solo lo que me toca', false);
    }
}
