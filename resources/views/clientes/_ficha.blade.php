{{-- Ficha del cliente en la ventana flotante del listado (ClienteController::show por fetch).
     Variables: $cliente (con ventas a crédito y abonos), $saldo, $disponible, $totalComprado, $ultimaCompra. --}}
@php
    $movimientos = $cliente->ventas->where('anulada', false)
        ->map(fn ($v) => ['fecha' => $v->fecha, 'texto' => 'Fiado, venta #' . $v->id, 'monto' => (float) $v->total, 'es_abono' => false])
        ->concat($cliente->abonos->map(fn ($a) => ['fecha' => $a->fecha, 'texto' => 'Abono' . ($a->moneda === 'VES' ? ' en Bs.' : ''), 'monto' => (float) $a->monto, 'es_abono' => true]))
        ->sortByDesc('fecha')
        ->take(6);
@endphp
<div class="space-y-5">
    <div class="text-xs text-stone-500 dark:text-stone-400 space-y-0.5">
        <p>Teléfono <span class="font-semibold text-stone-700 dark:text-stone-200">{{ $cliente->telefono ? \App\Support\Contacto::telefono($cliente->telefono) : 'sin teléfono' }}</span>@if($cliente->rif) · Cédula/RIF <span class="font-semibold text-stone-700 dark:text-stone-200">{{ $cliente->rif }}</span>@endif</p>
        @if($cliente->direccion)<p>Dirección <span class="font-semibold text-stone-700 dark:text-stone-200">{{ $cliente->direccion }}</span></p>@endif
        @if($cliente->notas)<p>Notas <span class="font-semibold text-stone-700 dark:text-stone-200">{{ $cliente->notas }}</span></p>@endif
        @unless($cliente->activo)<p><span class="insignia-gris">Inactivo: no se le fía en ventas nuevas</span></p>@endunless
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="caja-suave p-3">
            <p class="cifra-etiqueta">Debe</p>
            <x-precio :usd="$saldo" class="font-display text-lg font-bold {{ $saldo > 0 ? 'text-orange-700 dark:text-orange-400' : 'text-emerald-700 dark:text-emerald-400' }}" />
        </div>
        <div class="caja-suave p-3">
            <p class="cifra-etiqueta">Crédito libre</p>
            @if($disponible === null)
                <p class="font-display text-lg font-bold text-stone-800 dark:text-stone-100">Sin límite</p>
            @else
                <p class="font-display text-lg font-bold {{ $disponible > 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">@usd($disponible)</p>
                <p class="text-[11px] text-stone-500 dark:text-stone-400">de @usd($cliente->limite_credito)</p>
            @endif
        </div>
        <div class="caja-suave p-3">
            <p class="cifra-etiqueta">Comprado</p>
            <p class="font-display text-lg font-bold text-stone-800 dark:text-stone-100">@usd($totalComprado)</p>
        </div>
        <div class="caja-suave p-3">
            <p class="cifra-etiqueta">Última compra</p>
            <p class="font-display text-lg font-bold text-stone-800 dark:text-stone-100">{{ $ultimaCompra ? $ultimaCompra->format('d/m/Y') : '—' }}</p>
        </div>
    </div>

    <div>
        <h3 class="titulo-tarjeta mb-2">Últimos fiados y abonos</h3>
        @if($movimientos->isEmpty())
            <p class="text-sm text-stone-500 dark:text-stone-400">Todavía no tiene fiados ni abonos.</p>
        @else
            <ul class="divide-y divide-stone-200 dark:divide-stone-700 border-y border-stone-200 dark:border-stone-700">
                @foreach($movimientos as $mov)
                    <li class="flex items-center justify-between gap-3 py-2 text-sm">
                        <div class="min-w-0">
                            <p class="font-semibold text-stone-700 dark:text-stone-200">{{ $mov['texto'] }}</p>
                            <p class="text-[11px] text-stone-500 dark:text-stone-400">{{ $mov['fecha']->format('d/m/Y h:i A') }}</p>
                        </div>
                        <p class="font-bold shrink-0 {{ $mov['es_abono'] ? 'text-emerald-700 dark:text-emerald-400' : 'text-orange-700 dark:text-orange-400' }}">{{ $mov['es_abono'] ? '−' : '+' }}@usd($mov['monto'])</p>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
        <a href="{{ route('clientes.show', $cliente) }}" class="btn-primario">
            <x-icon name="banknote" class="w-4 h-4" /> {{ $saldo > 0 ? 'Cobrar abono y ver cuenta' : 'Ver cuenta completa' }}
        </a>
        @if(auth()->user()->esEncargadoOSuperior())
            <a href="{{ route('clientes.edit', $cliente) }}" data-editar class="btn-secundario">
                <x-icon name="edit" class="w-4 h-4" /> Editar
            </a>
        @endif
    </div>
</div>
