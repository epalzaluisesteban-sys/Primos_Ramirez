<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    | Disco de las fotos subidas (productos, clientes, personal, categorías).
    | 'public' en local; 's3' en producción (Supabase Storage). Ver App\Support\Almacen.
    */
    'media_disk' => env('MEDIA_DISK', 'public'),

    /*
    | Disco donde quedan los respaldos (App\Services\RespaldoService).
    | 'local' en la PC; 'respaldos' en Render, porque allá el disco del servidor se borra en cada reinicio.
    */
    'respaldos_disk' => env('RESPALDOS_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        // Disco privado: la app no entrega nada de aquí por URL. Con 'serve' en true su ruta ocupaba /storage/...
        // y respondía 403 a las fotos cuando faltaba el enlace public/storage.
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => false,
            'throw' => false,
            'report' => false,
        ],

        // Fotos (productos, clientes, personal, categorías). Normalmente el servidor las entrega por el enlace
        // public/storage (php artisan storage:link). Si el enlace falta —pasa al copiar el proyecto a otra
        // computadora en Windows— 'serve' hace que Laravel las entregue igual por /storage/{ruta}.
        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

        // Respaldos en producción: bucket PRIVADO de Supabase (traen hashes de contraseñas y datos de clientes).
        // 'throw' para que un respaldo que no se pudo guardar se note en vez de quedar anotado sin archivo.
        'respaldos' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET_RESPALDOS', 'respaldos'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'visibility' => 'private',
            'throw' => true,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
