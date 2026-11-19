<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\RestablecerClave;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * "¿Olvidaste tu contraseña?": enlace por correo que sirve una vez, sin revelar qué correos existen
 * y sin dejar entrar a usuarios desactivados.
 */
class RecuperarClaveTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->usuario = User::factory()->create(['email' => 'ana@correo.com', 'rol' => 'Obrero', 'activo' => true]);
    }

    public function test_el_login_enlaza_a_recuperar_y_la_pantalla_carga(): void
    {
        $this->get('/login')->assertSee(route('password.request'));
        $this->get(route('password.request'))->assertOk()->assertSee('Enviar enlace');
    }

    public function test_envia_el_enlace_aunque_el_correo_venga_con_mayusculas(): void
    {
        $this->post(route('password.email'), ['email' => '  Ana@Correo.COM '])
            ->assertSessionHas('enlace_enviado', 'ana@correo.com')
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($this->usuario, RestablecerClave::class, function (RestablecerClave $aviso) {
            $correo = $aviso->toMail($this->usuario);

            return str_contains($correo->actionUrl, '/restablecer-clave/' . $aviso->token)
                && str_contains($correo->actionUrl, 'email=ana%40correo.com');
        });
    }

    public function test_un_correo_que_no_existe_recibe_el_mismo_aviso(): void
    {
        $this->post(route('password.email'), ['email' => 'nadie@correo.com'])
            ->assertSessionHas('enlace_enviado')
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    public function test_un_usuario_desactivado_no_recibe_enlace(): void
    {
        $this->usuario->update(['activo' => false]);

        $this->post(route('password.email'), ['email' => 'ana@correo.com'])->assertSessionHas('enlace_enviado');

        Notification::assertNothingSent();
    }

    public function test_cambia_la_contrasena_y_el_enlace_no_sirve_dos_veces(): void
    {
        $token = Password::createToken($this->usuario);

        $this->get(route('password.reset', ['token' => $token, 'email' => 'ana@correo.com']))
            ->assertOk()
            ->assertSee('ana@correo.com');

        $datos = [
            'token' => $token,
            'email' => 'ana@correo.com',
            'password' => 'nueva-clave-123',
            'password_confirmation' => 'nueva-clave-123',
        ];

        $this->post(route('password.update'), $datos)
            ->assertRedirect(route('login'))
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('nueva-clave-123', $this->usuario->fresh()->password));

        $this->post(route('password.update'), ['password' => 'otra-clave-456', 'password_confirmation' => 'otra-clave-456'] + $datos)
            ->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('nueva-clave-123', $this->usuario->fresh()->password));

        $this->post('/login', ['email' => 'ana@correo.com', 'password' => 'nueva-clave-123'])->assertRedirect('/');
    }

    public function test_rechaza_token_falso_y_claves_que_no_coinciden(): void
    {
        $claveAnterior = $this->usuario->password;

        $this->post(route('password.update'), [
            'token' => 'inventado',
            'email' => 'ana@correo.com',
            'password' => 'nueva-clave-123',
            'password_confirmation' => 'nueva-clave-123',
        ])->assertSessionHasErrors('email');

        $this->post(route('password.update'), [
            'token' => Password::createToken($this->usuario),
            'email' => 'ana@correo.com',
            'password' => 'nueva-clave-123',
            'password_confirmation' => 'distinta-123',
        ])->assertSessionHasErrors('password');

        $this->assertSame($claveAnterior, $this->usuario->fresh()->password);
    }

    public function test_un_usuario_desactivado_no_puede_usar_un_enlace_viejo(): void
    {
        $token = Password::createToken($this->usuario);
        $this->usuario->update(['activo' => false]);
        $claveAnterior = $this->usuario->password;

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'ana@correo.com',
            'password' => 'nueva-clave-123',
            'password_confirmation' => 'nueva-clave-123',
        ])->assertSessionHasErrors('email');

        $this->assertSame($claveAnterior, $this->usuario->fresh()->password);
    }

    public function test_en_produccion_sin_servicio_de_correo_no_ofrece_el_enlace(): void
    {
        $this->app['env'] = 'production';
        config(['mail.default' => 'log']);
        // Fuera del entorno de pruebas Laravel vuelve a pedir el token CSRF
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $this->get(route('password.request'))->assertOk()->assertSee('todavía no puede enviar correos')->assertDontSee('Enviar enlace');
        $this->post(route('password.email'), ['email' => 'ana@correo.com'])->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    }
}
