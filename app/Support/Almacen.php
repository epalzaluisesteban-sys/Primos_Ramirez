<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Archivos subidos (fotos de productos, clientes, personal y categorías).
 * En local se guardan en storage/app/public; en producción (Render) el disco es temporal,
 * así que MEDIA_DISK=s3 los envía al almacenamiento de Supabase.
 */
class Almacen
{
    public static function disco(): string
    {
        return config('filesystems.media_disk', 'public');
    }

    public static function guardar(UploadedFile $archivo, string $carpeta): string
    {
        return $archivo->store($carpeta, self::disco());
    }

    public static function borrar(?string $ruta): void
    {
        if ($ruta) {
            Storage::disk(self::disco())->delete($ruta);
        }
    }

    public static function url(?string $ruta): ?string
    {
        if (!$ruta) {
            return null;
        }

        return self::disco() === 'public'
            ? asset('storage/' . $ruta)
            : Storage::disk(self::disco())->url($ruta);
    }
}
