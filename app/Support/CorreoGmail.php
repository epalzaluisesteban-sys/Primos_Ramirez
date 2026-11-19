<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\MessageConverter;

/**
 * Mailer "gmail": entrega el correo a un Apps Script de Google (ver DESPLIEGUE.md, paso 3.3) que lo envía desde la
 * cuenta de Gmail del negocio. Va por HTTPS porque Render gratis bloquea SMTP, y no exige tener dominio propio.
 */
class CorreoGmail extends AbstractTransport
{
    public function __construct(private ?string $url, private ?string $secreto)
    {
        parent::__construct();
    }

    public static function configurado(): bool
    {
        return filled(config('services.gmail.url')) && filled(config('services.gmail.secreto'));
    }

    protected function doSend(SentMessage $mensaje): void
    {
        $correo = MessageConverter::toEmail($mensaje->getOriginalMessage());

        $respuesta = Http::timeout(30)->post($this->url, [
            'secreto' => $this->secreto,
            'para' => implode(',', array_map(fn (Address $a) => $a->getAddress(), $correo->getTo())),
            'asunto' => $correo->getSubject(),
            'html' => $correo->getHtmlBody(),
            'texto' => $correo->getTextBody(),
            'nombre' => config('mail.from.name'),
        ]);

        if ($respuesta->json('ok') !== true) {
            throw new TransportException('Apps Script de Gmail: ' . ($respuesta->json('error') ?? "respuesta inesperada (HTTP {$respuesta->status()})"));
        }
    }

    public function __toString(): string
    {
        return 'gmail+apps-script';
    }
}
