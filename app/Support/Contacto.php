<?php

namespace App\Support;

/**
 * Teléfonos y documentos de identidad (cédula / RIF) de clientes y proveedores.
 *
 * Teléfono: se guarda solo con números, 11 dígitos empezando por 0 (04121234567, 02121234567).
 * Documento: se guarda como "V-12345678" (cédula) o "J-12345678-9" (RIF, 9 dígitos).
 */
class Contacto
{
    // Regla de validación para cualquier campo de teléfono (aplicar después de limpiarTelefono()).
    // El mensaje de error está en lang/es/validation.php → custom.
    public const REGLA_TELEFONO = ['nullable', 'regex:/^0\d{10}$/'];

    // V = venezolano, E = extranjero, J = jurídico (empresa), G = gobierno, P = pasaporte.
    public const TIPOS_DOCUMENTO = ['V', 'E', 'J', 'G', 'P'];

    /**
     * Quita los separadores que la gente suele escribir (espacios, guiones, puntos, paréntesis, +)
     * y pasa el formato internacional 58XXXXXXXXXX a 0XXXXXXXXXX. Las letras se dejan tal cual
     * para que la validación las rechace.
     */
    public static function limpiarTelefono(?string $telefono): ?string
    {
        $limpio = preg_replace('/[\s\-\.\(\)\+]/', '', (string) $telefono);

        if (preg_match('/^58(\d{10})$/', $limpio, $m)) {
            $limpio = '0' . $m[1];
        }

        return $limpio === '' ? null : $limpio;
    }

    // Para mostrar: 04121234567 → 0412-1234567. Si no tiene el formato esperado, se muestra como está.
    public static function telefono(?string $telefono): string
    {
        $limpio = self::limpiarTelefono($telefono);

        if ($limpio && preg_match('/^(0\d{3})(\d{7})$/', $limpio, $m)) {
            return "{$m[1]}-{$m[2]}";
        }

        return (string) $telefono;
    }

    // Número para wa.me (internacional sin 0 ni +): 04121234567 → 584121234567.
    public static function telefonoWhatsapp(?string $telefono): ?string
    {
        $limpio = preg_replace('/\D/', '', (string) self::limpiarTelefono($telefono));

        if (preg_match('/^0(\d{10})$/', $limpio, $m)) {
            return '58' . $m[1];
        }

        return $limpio ?: null;
    }

    /**
     * Separa un documento guardado en [tipo, número] para llenar el formulario.
     * "V-12.345.678" → ['V', '12345678'];  "J-12345678-9" → ['J', '123456789'].
     */
    public static function separarDocumento(?string $documento): array
    {
        $documento = strtoupper(trim((string) $documento));
        $tipo = in_array(substr($documento, 0, 1), self::TIPOS_DOCUMENTO, true) ? substr($documento, 0, 1) : 'V';

        return [$tipo, preg_replace('/\D/', '', $documento)];
    }

    // Une tipo y número: ('V', '12345678') → "V-12345678";  ('J', '123456789') → "J-12345678-9".
    public static function unirDocumento(?string $tipo, ?string $numero): ?string
    {
        $numero = preg_replace('/\D/', '', (string) $numero);
        if ($numero === '') {
            return null;
        }

        $tipo = strtoupper((string) $tipo) ?: 'V';

        return strlen($numero) === 9
            ? $tipo . '-' . substr($numero, 0, 8) . '-' . substr($numero, 8)
            : $tipo . '-' . $numero;
    }
}
