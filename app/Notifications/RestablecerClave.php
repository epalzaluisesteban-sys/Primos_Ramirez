<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Correo con el enlace para poner una contraseña nueva (lo pide RecuperarClaveController). */
class RestablecerClave extends Notification
{
    public function __construct(public string $token)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]);
        $minutos = config('auth.passwords.users.expire');

        return (new MailMessage)
            ->subject('Cambiar tu contraseña - Los Primos Ramírez')
            ->greeting('Hola, ' . $notifiable->name)
            ->line('Pediste cambiar la contraseña con la que entras al sistema de Los Primos Ramírez.')
            ->action('Poner una contraseña nueva', $url)
            ->line("El enlace sirve una sola vez y vence en {$minutos} minutos.")
            ->line('Si no lo pediste tú, no hagas nada: tu contraseña sigue igual.')
            ->salutation('Distribuidora Los Primos Ramírez');
    }
}
