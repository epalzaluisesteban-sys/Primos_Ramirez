{{-- Detalle de una caja (CajaController::show): ventana flotante del historial o página si se abre directo.
     Variables: las de CajaService::reporte() ($caja, $resumen, $esperado, $ventas, $fiados, $abonos, $gastos,
     $pagosProveedor, $totalVendido). --}}
@php
    $cerrada = $caja->estado === 'CERRADA';
    $claseDiferencia = fn ($d) => $d < 0 ? 'text-rose-600 dark:text-rose-400' : ($d > 0 ? 'text-blue-600 dark:text-blue-400' : 'text-emerald-600 dark:text-emerald-400');
    $dia = \Illuminate\Support\Carbon::parse($caja->fecha)->toDateString();
@endphp
<div class="space-y-5">
    <div class="flex flex-wrap items-center gap-2 text-xs text-stone-500 dark:text-stone-400">
        @if($cerrada)
            <span class="insignia-gris">Cerrada</span>
        @else
            <span class="insignia-ambar">Abierta</span>
        @endif
        <span>Abrió {{ $caja->usuario->name ?? 'Sistema' }}</span>
        @if($cerrada)
            <span>· Cerró {{ $caja->cerradaPor->name ?? 'Sistema' }}{{ $caja->cerrada_at ? ' a las ' . $caja->cerrada_at->format('h:i A') : '' }}</span>
        @endif
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="caja-suave p-3">
            <p class="cifra-etiqueta">Vendido</p>
            <p class="font-display text-lg font-bold text-stone-800 dark:text-stone-100">@usd($totalVendido)</p>
            <p class="text-[11px] text-stone-500 dark:text-stone-400">{{ $ventas->count() }} {{ $ventas->count() === 1 ? 'venta' : 'ventas' }}</p>
        </div>
        <div class="caja-suave p-3">
            <p class="cifra-etiqueta">Fiado</p>
            <p class="font-display text-lg font-bold text-orange-700 dark:text-orange-400">@usd($resumen['credito'])</p>
            <p class="text-[11px] text-stone-500 dark:text-stone-400">{{ $fiados->count() }} {{ $fiados->count() === 1 ? 'venta' : 'ventas' }}</p>
        </div>
        <div class="caja-suave p-3">
            <p class="cifra-etiqueta">Abonos</p>
            <p class="font-display text-lg font-bold text-emerald-700 dark:text-emerald-400">{{ $abonos->count() }}</p>
        </div>
        <div class="caja-suave p-3">
            <p class="cifra-etiqueta">Gastos</p>
            <p class="font-display text-lg font-bold text-rose-700 dark:text-rose-400">{{ $gastos->count() + $pagosProveedor->count() }}</p>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="tabla">
            <thead>
                <tr><th>Cuadre</th><th class="text-right">Dólares</th><th class="text-right">Bolívares</th></tr>
            </thead>
            <tbody>
                <tr><td>Fondo inicial</td><td class="text-right whitespace-nowrap">@usd($caja->monto_apertura)</td><td class="text-right whitespace-nowrap">@ves($caja->monto_apertura_ves)</td></tr>
                <tr><td>Ventas de contado</td><td class="text-right whitespace-nowrap text-emerald-700 dark:text-emerald-400">@usd($resumen['contado_usd'])</td><td class="text-right whitespace-nowrap text-emerald-700 dark:text-emerald-400">@ves($resumen['contado_ves'])</td></tr>
                <tr><td>Abonos cobrados</td><td class="text-right whitespace-nowrap text-emerald-700 dark:text-emerald-400">@usd($resumen['abonos_usd'])</td><td class="text-right whitespace-nowrap text-emerald-700 dark:text-emerald-400">@ves($resumen['abonos_ves'])</td></tr>
                <tr><td>Gastos</td><td class="text-right whitespace-nowrap text-rose-700 dark:text-rose-400">− @usd($resumen['gastos_usd'])</td><td class="text-right whitespace-nowrap text-rose-700 dark:text-rose-400">− @ves($resumen['gastos_ves'])</td></tr>
                @if($resumen['pagos_usd'] > 0 || $resumen['pagos_ves'] > 0)
                    <tr><td>Pagos a proveedores</td><td class="text-right whitespace-nowrap text-rose-700 dark:text-rose-400">− @usd($resumen['pagos_usd'])</td><td class="text-right whitespace-nowrap text-rose-700 dark:text-rose-400">− @ves($resumen['pagos_ves'])</td></tr>
                @endif
                <tr class="font-bold text-stone-900 dark:text-stone-100"><td>Esperado en caja</td><td class="text-right whitespace-nowrap">@usd($esperado['usd'])</td><td class="text-right whitespace-nowrap">@ves($esperado['ves'])</td></tr>
                @if($cerrada)
                    <tr><td>Efectivo contado</td><td class="text-right whitespace-nowrap">@usd($caja->monto_efectivo_real)</td><td class="text-right whitespace-nowrap">@ves($caja->monto_efectivo_real_ves)</td></tr>
                    <tr class="font-bold"><td>Diferencia</td>
                        <td class="text-right whitespace-nowrap {{ $claseDiferencia($caja->diferencia) }}">@usd($caja->diferencia)</td>
                        <td class="text-right whitespace-nowrap {{ $claseDiferencia($caja->diferencia_ves) }}">@ves($caja->diferencia_ves)</td></tr>
                @endif
            </tbody>
        </table>
    </div>

    @if($caja->observaciones)
        <div class="caja-suave p-3 text-xs text-stone-600 dark:text-stone-300 whitespace-pre-line">{{ $caja->observaciones }}</div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
        <a href="{{ route('caja.reporte', $caja) }}" class="btn-primario">
            <x-icon name="download" class="w-4 h-4" /> Reporte PDF
        </a>
        <a href="{{ route('caja.ticket', $caja) }}" target="_blank" rel="noopener" class="btn-secundario">
            <x-icon name="printer" class="w-4 h-4" /> Ticket
        </a>
        <a href="{{ route('gastos.index', ['fecha_inicio' => $dia, 'fecha_fin' => $dia]) }}" class="btn-secundario">
            <x-icon name="trending-down" class="w-4 h-4" /> Gastos del día
        </a>
    </div>
</div>
