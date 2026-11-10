<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mientras se restaura un respaldo nadie puede guardar cambios (ventas, abonos, compras…):
 * se perderían al reemplazar la información. Las páginas se pueden seguir viendo.
 */
class BloquearDuranteRestauracion
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->isMethodSafe() && !$request->routeIs('respaldos.*', 'login.post', 'logout')
            && Cache::get('restauracion_en_curso')) {
            return back()->withInput()->withErrors([
                'error' => 'Se está restaurando un respaldo. Espera un minuto y vuelve a intentarlo.',
            ]);
        }

        return $next($request);
    }
}
