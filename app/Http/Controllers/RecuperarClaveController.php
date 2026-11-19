<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Exception\ExceptionInterface as MailerException;

/**
 * "¿Olvidaste tu contraseña?": se manda al correo un enlace que vence en 60 minutos (config/auth.php)
 * y sirve una sola vez. Los usuarios desactivados no reciben enlace ni pueden usarlo.
 */
class RecuperarClaveController extends Controller
{
    /** En producción con MAIL_MAILER=log, o Brevo sin su clave, el correo no sale de verdad: no se ofrece el enlace. */
    public static function correoDisponible(): bool
    {
        $mailer = config('mail.default');

        if ($mailer === 'brevo') {
            return filled(config('services.brevo.key'));
        }

        return !(app()->isProduction() && in_array($mailer, ['log', 'array'], true));
    }

    public function pedir()
    {
        return view('auth.olvide-clave', ['correoDisponible' => self::correoDisponible()]);
    }

    public function enviar(Request $request)
    {
        if (!self::correoDisponible()) {
            return back()->withErrors(['email' => 'El sistema todavía no puede enviar correos. Pídele al dueño que te cambie la contraseña desde Personal.']);
        }

        $request->merge(['email' => User::normalizarCorreo($request->input('email'))]);
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        try {
            $estado = Password::sendResetLink(['email' => $request->email, 'activo' => true]);
        } catch (MailerException $e) {
            // Brevo caído, clave equivocada o remitente sin verificar: queda en el log y la persona sabe a quién acudir
            report($e);

            return back()->withErrors([
                'email' => 'No se pudo enviar el correo en este momento. Pídele al dueño que te cambie la contraseña desde Personal.',
            ])->withInput();
        }

        if ($estado === Password::RESET_THROTTLED) {
            return back()->withErrors(['email' => __($estado)])->withInput();
        }

        // Mismo aviso exista o no la cuenta: así nadie puede averiguar qué correos están registrados
        return back()->with('enlace_enviado', $request->email);
    }

    public function formulario(Request $request, string $token)
    {
        return view('auth.restablecer-clave', [
            'token' => $token,
            'email' => User::normalizarCorreo($request->query('email')),
        ]);
    }

    public function guardar(Request $request)
    {
        $request->merge(['email' => User::normalizarCorreo($request->input('email'))]);
        $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
        ], [
            'password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las dos contraseñas no coinciden. Escríbelas otra vez.',
        ]);

        $estado = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token') + ['activo' => true],
            function (User $usuario, string $clave) {
                $usuario->forceFill([
                    'password' => Hash::make($clave),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($usuario));
            }
        );

        if ($estado !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => __($estado)])->withInput($request->only('email'));
        }

        return redirect()->route('login')
            ->with('success', __($estado))
            ->withInput(['email' => $request->email]);
    }
}
