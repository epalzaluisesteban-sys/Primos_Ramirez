{{-- Encabezado de toda página: título, subtítulo (para qué sirve), acciones y botón "Volver".
     Uso: <x-encabezado titulo="Proveedores" subtitulo="Quién te surte la mercancía." :volver="route('...')">
              <a class="btn-primario">…</a>
          </x-encabezado>
     "Volver" es siempre el mismo botón y va siempre arriba a la derecha, también en el teléfono;
     las acciones quedan a su izquierda (o bajo el título si no caben).
     Slots opcionales: "inicio" (foto o inicial a la izquierda del título) y "detalle" (datos bajo el título). --}}
@props(['titulo', 'subtitulo' => null, 'volver' => null])

<div {{ $attributes->merge(['class' => 'encabezado-pagina']) }}>
    <div class="flex-1 min-w-0 flex flex-wrap items-start justify-between gap-x-4 gap-y-3">
        <div class="flex items-center gap-4 min-w-0">
            {{ $inicio ?? '' }}
            <div class="min-w-0">
                <h1 class="titulo-pagina">{{ $titulo }}</h1>
                @if($subtitulo)
                    <p class="subtitulo-pagina">{{ $subtitulo }}</p>
                @endif
                {{ $detalle ?? '' }}
            </div>
        </div>
        @if($slot->isNotEmpty())
            <div class="no-print flex flex-wrap items-center gap-2">{{ $slot }}</div>
        @endif
    </div>
    @if($volver)
        <a href="{{ $volver }}" class="no-print btn-secundario btn-sm shrink-0">
            <x-icon name="arrow-left" class="w-4 h-4" /> Volver
        </a>
    @endif
</div>
