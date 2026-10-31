<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Si no está autenticado o su rol no está en la lista de autorizados
        if (!auth()->check() || !auth()->user()->tieneRol($roles)) {
            abort(403, 'Acceso denegado: No tienes los permisos necesarios para realizar esta acción.');
        }

        return $next($request);
    }
}