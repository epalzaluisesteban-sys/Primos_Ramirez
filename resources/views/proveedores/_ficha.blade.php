{{-- Ficha del proveedor en la ventana flotante del listado (ProveedorController::show por fetch).
     Variables: $proveedor, $compras y $pagos (del más reciente al más viejo), $deuda, $totalComprado, $ultimaCompra. --}}
<div class="space-y-5">
    <div class="text-xs text-stone-500 dark:text-stone-400 space-y-0.5">
        <p>Contacto <span class="font-semibold text-stone-700 dark:text-stone-200">{{ $proveedor->contacto ?: 'sin persona de contacto' }}</span>@if($proveedor->telefono) · Tel. <span class="font-semibold text-stone-700 dark:text-stone-200">{{ \App\Support\Contacto::telefono($proveedor->telefono) }}</span>@endif</p>
        @if($proveedor->rif)<p>RIF <span class="font-semibold text-stone-700 dark:text-stone-200">{{ $proveedor->rif }}</span></p>@endif
        <p>Distribuye <span class="font-semibold text-stone-700 dark:text-stone-200">{{ $proveedor->categorias->pluck('nombre')->join(', ') ?: 'sin categorías marcadas' }}</span>
            · {{ $proveedor->da_credito ? 'Da crédito' . ($proveedor->dias_credito ? " a {$proveedor->dias_credito} días" : '') : 'No da crédito' }}</p>
        @unless($proveedor->activo)<p><span class="insignia-gris">Inactivo: no sale en compras nuevas</span></p>@endunless
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="caja-suave p-3">
            <p class="cifra-etiqueta">Le debemos</p>
            <p class="font-display text-lg font-bold {{ $deuda['saldo'] > 0 ? 'text-orange-700 dark:text-orange-400' : 'text-stone-800 dark:text-stone-100' }}">@usd($deuda['saldo'])</p>
            @if($deuda['saldo'] > 0 && $deuda['vence_el'])
                <p class="text-[11px] {{ $deuda['vencido'] ? 'text-rose-600 dark:text-rose-400 font-semibold' : 'text-stone-500 dark:text-stone-400' }}">{{ $deuda['vencido'] ? 'Vencido el' : 'Vence el' }} {{ $deuda['vence_el']->format('d/m/Y') }}</p>
            @endif
        </div>
        <div class="caja-suave p-3">
            <p class="cifra-etiqueta">Comprado</p>
            <p class="font-display text-lg font-bold text-stone-800 dark:text-stone-100">@usd($totalComprado)</p>
        </div>
        <div class="caja-suave p-3">
            <p class="cifra-etiqueta">Compras</p>
            <p class="font-display text-lg font-bold text-stone-800 dark:text-stone-100">{{ $compras->count() }}</p>
        </div>
        <div class="caja-suave p-3">
            <p class="cifra-etiqueta">Última compra</p>
            <p class="font-display text-lg font-bold text-stone-800 dark:text-stone-100">{{ $ultimaCompra ? $ultimaCompra->format('d/m/Y') : '—' }}</p>
        </div>
    </div>

    <div>
        <h3 class="titulo-tarjeta mb-2">Últimas compras</h3>
        @if($compras->isEmpty())
            <p class="text-sm text-stone-500 dark:text-stone-400">Todavía no se le ha comprado nada.</p>
        @else
            <ul class="divide-y divide-stone-200 dark:divide-stone-700 border-y border-stone-200 dark:border-stone-700">
                @foreach($compras->take(5) as $compra)
                    <li class="flex items-center justify-between gap-3 py-2 text-sm">
                        <div class="min-w-0">
                            <p class="font-semibold text-stone-700 dark:text-stone-200 truncate">{{ $compra->producto->nombre ?? 'Producto eliminado' }} <span class="font-normal text-stone-500 dark:text-stone-400">× {{ \App\Support\Cantidad::formato($compra->cantidad) }}</span></p>
                            <p class="text-[11px] text-stone-500 dark:text-stone-400">{{ \Illuminate\Support\Carbon::parse($compra->fecha)->format('d/m/Y') }} · {{ $compra->tipo_pago === 'credito' ? 'A crédito' : 'De contado' }}</p>
                        </div>
                        <p class="font-bold shrink-0 text-stone-800 dark:text-stone-100">@usd($compra->total)</p>
                    </li>
                @endforeach
            </ul>
        @endif
        @if($pagos->isNotEmpty())
            <p class="ayuda-campo">Último pago: @usd($pagos->first()->monto) el {{ $pagos->first()->fecha->format('d/m/Y') }}.</p>
        @endif
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
        <a href="{{ route('proveedores.show', $proveedor) }}" class="btn-primario">
            <x-icon name="truck" class="w-4 h-4" /> {{ $deuda['saldo'] > 0 ? 'Pagar y ver todo' : 'Ver todo' }}
        </a>
        <a href="{{ route('proveedores.edit', $proveedor) }}" data-editar class="btn-secundario">
            <x-icon name="edit" class="w-4 h-4" /> Editar
        </a>
        @if($proveedor->whatsappUrl())
            <a href="{{ $proveedor->whatsappUrl() }}" target="_blank" rel="noopener" class="btn-secundario">
                <x-icon name="whatsapp" class="w-4 h-4" /> WhatsApp
            </a>
        @endif
    </div>
</div>
