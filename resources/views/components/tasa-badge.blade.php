@props(['tasa' => null])

{{-- Tasa oficial BCV vigente. Para Encargado/Dueño enlaza a la pantalla de tasa de cambio. --}}
@php
    $contenido = $tasa
        ? 'BCV <strong class="font-extrabold">Bs. ' . number_format($tasa->tasa, 2, ',', '.') . '</strong> / $1'
        : 'Sin tasa BCV';
    $titulo = $tasa
        ? 'Tasa oficial BCV al ' . $tasa->fecha->format('d/m/Y') . ($tasa->esAutomatica() ? '' : ' (manual)')
        : 'No hay tasa de cambio cargada';
    $clases = 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold border transition '
        . ($tasa ? 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800'
                 : 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-900/30 dark:text-rose-300 dark:border-rose-800');
@endphp

@if(auth()->user()->esEncargadoOSuperior())
    <a href="{{ route('tasa.index') }}" title="{{ $titulo }}" class="{{ $clases }} hover:brightness-95">
        <x-icon name="banknote" class="w-3.5 h-3.5" /> {!! $contenido !!}
    </a>
@else
    <span title="{{ $titulo }}" class="{{ $clases }}">
        <x-icon name="banknote" class="w-3.5 h-3.5" /> {!! $contenido !!}
    </span>
@endif
