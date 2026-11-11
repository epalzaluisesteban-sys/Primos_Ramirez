<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
   ->withMiddleware(function (Middleware $middleware) {
    // Detrás del proxy de Render (HTTPS termina allá): confiar en X-Forwarded-* para generar URLs https
    $middleware->trustProxies(at: '*');
    $middleware->alias([
        'role' => \App\Http\Middleware\CheckRole::class,
    ]);
    // Mientras se restaura un respaldo no se aceptan cambios en el resto del sistema
    $middleware->web(append: [\App\Http\Middleware\BloquearDuranteRestauracion::class]);
})
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
