<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Symfony\Component\HttpFoundation\Response;

/**
 * Formularios de editar que se abren en la ventana flotante (pedidos por fetch, campo oculto `_editar_en`
 * con la URL de editar). Si vuelven con errores:
 *  1. La página a la que regresa no usa lo escrito ni los errores, para no llenar con eso el
 *     formulario de "Nuevo…" de esa pantalla, y le dice al layout qué ventana reabrir.
 *  2. El fetch que la reabre recibe lo escrito y los errores, como si fuera la pantalla de editar.
 */
class ReabrirEdicionEnVentana
{
    public function handle(Request $request, Closure $next): Response
    {
        $sesion = $request->session();
        $url = $sesion->get('_old_input._editar_en');

        if (is_string($url) && $request->isMethod('GET') && ! $request->ajax()) {
            if (str_starts_with($url, url('/') . '/')) {
                $errores = $sesion->get('errors') ?: new ViewErrorBag;
                $sesion->flash('_edicion_pendiente', [
                    'url' => $url,
                    'datos' => $sesion->get('_old_input'),
                    'errores' => $errores,
                ]);
                View::share('edicionPendiente', ['url' => $url, 'mensaje' => $errores->first()]);
            }
            $sesion->forget(['_old_input', 'errors']);
            View::share('errors', new ViewErrorBag);
        } elseif ($request->ajax() && ($pendiente = $sesion->get('_edicion_pendiente')) && $pendiente['url'] === $request->fullUrl()) {
            $sesion->forget('_edicion_pendiente');
            $sesion->now('_old_input', $pendiente['datos']);
            View::share('errors', $pendiente['errores']);
        }

        return $next($request);
    }
}
