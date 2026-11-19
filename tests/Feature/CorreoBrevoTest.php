<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoApiTransport;
use Symfony\Component\Mailer\Exception\HttpTransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Tests\TestCase;

/**
 * En Render el correo sale por la API de Brevo (MAIL_MAILER=brevo). Sin clave no se ofrece el enlace,
 * y si Brevo lo rechaza la persona sabe a quién pedirle la contraseña.
 */
class CorreoBrevoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        User::factory()->create(['email' => 'ana@correo.com', 'activo' => true]);
        config(['mail.default' => 'brevo']);
    }

    public function test_el_mailer_brevo_usa_la_api_con_la_clave_configurada(): void
    {
        config(['services.brevo.key' => 'xkeysib-prueba']);

        $transporte = Mail::mailer('brevo')->getSymfonyTransport();

        $this->assertInstanceOf(BrevoApiTransport::class, $transporte);
        $this->assertSame('brevo+api://api.brevo.com', (string) $transporte);
    }

    public function test_sin_clave_de_brevo_no_se_ofrece_el_enlace(): void
    {
        config(['services.brevo.key' => null]);

        $this->get(route('password.request'))->assertSee('todavía no puede enviar correos')->assertDontSee('Enviar enlace');
        $this->post(route('password.email'), ['email' => 'ana@correo.com'])->assertSessionHasErrors('email');
    }

    public function test_si_brevo_rechaza_el_correo_se_avisa_que_lo_cambie_el_dueno(): void
    {
        config(['services.brevo.key' => 'xkeysib-vencida']);
        Mail::extend('brevo', fn () => new class extends AbstractTransport {
            protected function doSend(SentMessage $mensaje): void
            {
                $respuesta = \Mockery::mock(ResponseInterface::class);
                $respuesta->shouldReceive('getStatusCode')->andReturn(401);
                throw new HttpTransportException('Key not found', $respuesta);
            }

            public function __toString(): string
            {
                return 'brevo+api://api.brevo.com';
            }
        });

        $this->post(route('password.email'), ['email' => 'ana@correo.com'])
            ->assertSessionHasErrors(['email' => 'No se pudo enviar el correo en este momento. Pídele al dueño que te cambie la contraseña desde Personal.'])
            ->assertSessionMissing('enlace_enviado');
    }
}
