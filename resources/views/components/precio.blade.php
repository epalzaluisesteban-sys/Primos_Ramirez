@props(['usd', 'tasa' => null, 'ves' => null, 'size' => 'normal'])

{{-- Muestra un monto en $ y debajo su equivalente en Bs. (tasa BCV vigente, o la guardada en la operación).
     Si se conoce el monto exacto en Bs. de la operación (ventas), se pasa en :ves. --}}
@php
    $montoVes = $ves ?? \App\Support\Moneda::aVes($usd, $tasa);
    $hayTasa = $ves !== null || ($tasa ?: \App\Support\Moneda::tasa()) > 0;
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex flex-col leading-tight']) }}>
    <span class="font-bold">{{ \App\Support\Moneda::usd($usd) }}</span>
    @if($hayTasa)
        <span class="{{ $size === 'small' ? 'text-[10px]' : 'text-[11px]' }} font-medium text-stone-500 dark:text-stone-400">{{ \App\Support\Moneda::ves($montoVes) }}</span>
    @endif
</span>
