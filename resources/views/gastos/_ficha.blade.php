{{-- Ficha de un gasto (ventana flotante de Gastos y página gastos.show). Variables: $gasto (con usuario y caja).
     Para corregir un gasto se quita y se anota de nuevo (solo con la caja de ese día abierta). --}}
@php $enBolivares = $gasto->moneda === 'VES'; @endphp
<div class="space-y-5">
    <div class="text-xs text-stone-500 dark:text-stone-400 space-y-0.5">
        <p class="text-sm font-bold text-stone-800 dark:text-stone-100">{{ $gasto->concepto }}</p>
        <p>El {{ $gasto->fecha->format('d/m/Y h:i A') }} · Anotó <span class="font-semibold text-stone-700 dark:text-stone-200">{{ $gasto->usuario->name ?? 'Sistema' }}</span></p>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
        <div class="caja-suave p-3">
            <p class="cifra-etiqueta">Monto</p>
            <p class="font-display text-lg font-bold text-rose-700 dark:text-rose-400">{{ $enBolivares ? \App\Support\Moneda::ves($gasto->monto_moneda) : \App\Support\Moneda::usd($gasto->monto_moneda) }}</p>
            @if($enBolivares)
                <p class="text-[11px] text-stone-500 dark:text-stone-400">≈ @usd($gasto->monto) a {{ \App\Support\Moneda::ves($gasto->tasa_cambio) }} por $</p>
            @endif
        </div>
        <div class="caja-suave p-3">
            <p class="cifra-etiqueta">Tipo</p>
            <p class="mt-1"><span class="insignia-gris">{{ $gasto->nombreCategoria() }}</span></p>
        </div>
        <div class="caja-suave p-3 col-span-2 sm:col-span-1">
            <p class="cifra-etiqueta">Pagado en</p>
            <p class="font-display text-lg font-bold text-stone-800 dark:text-stone-100">{{ $enBolivares ? 'Bolívares' : 'Dólares' }}</p>
            <p class="text-[11px] text-stone-500 dark:text-stone-400">Se resta del efectivo de la caja</p>
        </div>
    </div>

    @if($gasto->caja)
        <a href="{{ route('caja.show', $gasto->caja) }}" class="btn-secundario w-full">
            <x-icon name="banknote" class="w-4 h-4" /> Ver la caja del {{ $gasto->caja->fechaCorta() }}
        </a>
    @endif
</div>
