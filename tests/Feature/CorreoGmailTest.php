<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as PeticionHttp;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * En Render el correo sale desde Gmail por un Apps Script (MAIL_MAILER=gmail). Sin el script configurado no se ofrece
 * el enlace, y si el script lo rechaza la persona sabe a quién pedirle la contraseña.
 */
class CorreoGmailTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'https://script.google.com/macros/s/prueba/exec';

    protected function setUp(): void
    {
        parent::setUp();
        User::factory()->create(['name' => 'Ana', 'email' => 'ana@correo.com', 'activo' => true]);
        config([
            'mail.default' => 'gmail',
            'mail.from.name' => 'Los Primos Ramírez',
            'services.gmail.url' => self::URL,
            'services.gmail.secreto' => 'secreto-de-prueba',
        ]);
    }

    public function test_manda_el_enlace_al_script_con_el_secreto(): void
    {
        Http::fake([self::URL => Http::response(['ok' => true])]);

        $this->post(route('password.email'), ['email' => 'ana@correo.com'])
            ->assertSessionHas('enlace_enviado')
            ->assertSessionHasNoErrors();

        Http::assertSent(fn (PeticionHttp $peticion) => $peticion->url() === self::URL
            && $peticion['secreto'] === 'secreto-de-prueba'
            && $peticion['para'] === 'ana@correo.com'
            && $peticion['nombre'] === 'Los Primos Ramírez'
            && str_contains($peticion['asunto'], 'Cambiar tu contraseña')
            && str_contains($peticion['html'], '/restablecer-clave/')
            && str_contains($peticion['texto'], 'Hola, Ana'));
    }

    public function test_si_el_script_lo_rechaza_se_avisa_que_lo_cambie_el_dueno(): void
    {
        Http::fake([self::URL => Http::response(['ok' => false, 'error' => 'Clave secreta incorrecta'])]);

        $this->post(route('password.email'), ['email' => 'ana@correo.com'])
            ->assertSessionHasErrors(['email' => 'No se pudo enviar el correo en este momento. Pídele al dueño que te cambie la contraseña desde Personal.'])
            ->assertSessionMissing('enlace_enviado');
    }

    public function test_sin_la_direccion_del_script_no_se_ofrece_el_enlace(): void
    {
        config(['services.gmail.url' => null]);
        Http::fake();

        $this->get(route('password.request'))->assertSee('todavía no puede enviar correos')->assertDontSee('Enviar enlace');
        $this->post(route('password.email'), ['email' => 'ana@correo.com'])->assertSessionHasErrors('email');

        Http::assertNothingSent();
    }
}
