{{-- Filtros de un listado o reporte. En el celular quedan plegados detrás de un botón (con cuántos hay puestos)
     para no empujar la tabla hacia abajo; desde 640 px se ven siempre. Uso:
     <x-filtros id="filtros-ventas" :activos="count($filtros)"> <form method="GET">…</form> </x-filtros> --}}
@props(['id', 'activos' => 0, 'abierto' => null])

@php $abierto = $abierto ?? $errors->any(); @endphp
<div {{ $attributes }}>
    <button type="button" onclick="alternarFiltros(this)" aria-controls="{{ $id }}" aria-expanded="{{ $abierto ? 'true' : 'false' }}"
            class="sm:hidden btn-secundario w-full justify-between">
        <span class="inline-flex items-center gap-2">
            <x-icon name="search" class="w-4 h-4" /> Filtros
            @if($activos > 0) <span class="insignia-verde">{{ $activos }} {{ $activos === 1 ? 'puesto' : 'puestos' }}</span> @endif
        </span>
        <x-icon name="chevron-down" class="w-4 h-4" />
    </button>
    <div id="{{ $id }}" class="{{ $abierto ? '' : 'hidden' }} sm:block mt-3 sm:mt-0">
        {{ $slot }}
    </div>
</div>
