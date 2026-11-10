<?php

namespace App\Providers;

use App\Models\CierreCaja;
use App\Models\Producto;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL; // <-- 1. AGREGA ESTA LÍNEA AQUÍ ARRIBA

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
       
// HTTPS solo en producción; en local (artisan serve) se usa http
        // @usd($x) -> $1,234.50 | @ves($x) -> Bs. 1.234,50 | @bs($usd) -> equivalente en Bs. a la tasa BCV vigente
        Blade::directive('usd', fn ($e) => "<?php echo \App\Support\Moneda::usd({$e}); ?>");
        Blade::directive('ves', fn ($e) => "<?php echo \App\Support\Moneda::ves({$e}); ?>");
        Blade::directive('bs', fn ($e) => "<?php echo \App\Support\Moneda::usdEnVes({$e}); ?>");
        // @cantidad($x) -> 3.000 se muestra "3", 2.500 se muestra "2.5" (sin ceros de sobra)
        Blade::directive('cantidad', fn ($e) => "<?php echo \App\Support\Cantidad::formato({$e}); ?>");

        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
 // Notificaciones del sistema: se calculan en cada request y se comparten
        // con el partial de navegación (visible en todas las páginas autenticadas).
        View::composer('partials.nav', function ($view) {
            if (!auth()->check()) {
                return;
            }

            app(\App\Services\TasaCambioService::class)->refrescarSiHaceFalta();
            $view->with('tasaActual', \App\Services\TasaCambioService::actual());

            $notificaciones = collect();

            // Los avisos de productos llevan al filtro correspondiente de Productos (donde están las alertas).
            // El Obrero no entra a Productos: a él se le muestran como información, sin enlace.
            $veProductos = auth()->user()->esEncargadoOSuperior();
            $urlFiltro = fn (string $filtro) => $veProductos ? route('productos.index', ['filtro' => $filtro]) : null;

            $agotados = Producto::agotados()->get();
            foreach ($agotados->take(5) as $prod) {
                $notificaciones->push([
                    'tipo' => 'agotado',
                    'icono' => 'ban',
                    'color' => 'rose',
                    'texto' => "Producto agotado: {$prod->nombre}",
                    'url' => $urlFiltro('agotados'),
                ]);
            }

            $stockCritico = Producto::stockCritico()->get();
            foreach ($stockCritico->take(5) as $prod) {
                $notificaciones->push([
                    'tipo' => 'stock',
                    'icono' => 'alert-triangle',
                    'color' => 'rose',
                    'texto' => 'Quedan ' . \App\Support\Cantidad::formato($prod->stock) . " {$prod->unidadLabel()} de {$prod->nombre}",
                    'url' => $urlFiltro('stock_bajo'),
                ]);
            }

            if (auth()->user()->esEncargadoOSuperior()) {
                $porVencer = Producto::activos()->whereNotNull('fecha_vencimiento')
                    ->whereDate('fecha_vencimiento', '<=', now()->addDays(\App\Services\AlertasInventarioService::DIAS_AVISO_VENCIMIENTO))
                    ->orderBy('fecha_vencimiento')
                    ->get();
                foreach ($porVencer->take(5) as $prod) {
                    $dias = $prod->diasParaVencer();
                    $texto = $dias < 0
                        ? "{$prod->nombre} vencido hace " . abs($dias) . ' día(s)'
                        : ($dias == 0 ? "{$prod->nombre} vence hoy" : "{$prod->nombre} vence en {$dias} día(s)");
                    $notificaciones->push([
                        'tipo' => 'vencimiento',
                        'icono' => 'clock',
                        'color' => 'amber',
                        'texto' => $texto,
                        'url' => $urlFiltro('vencimiento'),
                    ]);
                }

                $cajaHoy = CierreCaja::whereDate('fecha', now()->toDateString())->first();
                if (!$cajaHoy || $cajaHoy->estado === 'ABIERTA') {
                    $notificaciones->push([
                        'tipo' => 'caja',
                        'icono' => 'banknote',
                        'color' => 'blue',
                        'texto' => $cajaHoy ? 'La caja de hoy sigue abierta, recuerda cerrarla.' : 'Aún no se ha abierto la caja de hoy.',
                        'url' => route('caja.index'),
                    ]);
                }
            }

            $view->with('notificaciones', $notificaciones);
        });
    }
}
