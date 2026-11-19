{{-- Ficha de una pérdida (ventana flotante de Pérdidas y página mermas.show). Variables: $merma (con producto y usuario).
     Las pérdidas no se editan: ya restaron del stock y del kardex. --}}
<div class="space-y-5">
    <div class="flex items-center gap-3">
        @if($merma->producto?->fotoUrl())
            <img src="{{ $merma->producto->fotoUrl() }}" alt="{{ $merma->producto->nombre }}" class="w-14 h-14 rounded-md object-cover border border-stone-200 dark:border-stone-700 shrink-0">
        @else
            <span class="w-14 h-14 rounded-md bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300 flex items-center justify-center border border-stone-200 dark:border-stone-700 shrink-0">
                <x-icon name="package-x" class="w-6 h-6" />
            </span>
        @endif
        <div class="min-w-0 text-xs text-stone-500 dark:text-stone-400 space-y-0.5">
            <p class="text-sm font-bold text-stone-800 dark:text-stone-100">{{ $merma->producto->nombre ?? 'Producto eliminado' }}</p>
            <p>El {{ $merma->fecha->format('d/m/Y h:i A') }} · Registró <span class="font-semibold text-stone-700 dark:text-stone-200">{{ $merma->usuario->name ?? 'Sistema' }}</span></p>
        </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
        <div class="caja-suave p-3">
            <p class="cifra-etiqueta">Cantidad perdida</p>
            <p class="font-display text-lg font-bold text-rose-600 dark:text-rose-400">
                −@cantidad($merma->cantidad) <span class="text-xs font-normal text-stone-500 dark:text-stone-400">{{ $merma->producto?->unidadLabel() }}</span>
            </p>
        </div>
        <div class="caja-suave p-3">
            <p class="cifra-etiqueta">Motivo</p>
            <p class="mt-1"><span class="insignia-roja">{{ $merma->motivoLabel() }}</span></p>
        </div>
        <div class="caja-suave p-3 col-span-2 sm:col-span-1">
            <p class="cifra-etiqueta">Valor aproximado</p>
            <p class="font-display text-lg font-bold text-stone-800 dark:text-stone-100">@usd($merma->valorEstimado())</p>
            <p class="text-[11px] text-stone-500 dark:text-stone-400">Al precio de venta de hoy</p>
        </div>
    </div>

    <div>
        <p class="cifra-etiqueta">Observación</p>
        <p class="text-sm text-stone-700 dark:text-stone-200">{{ $merma->observacion ?: 'Sin observación.' }}</p>
    </div>

    @if($merma->producto)
        <a href="{{ route('productos.kardex', $merma->producto) }}" class="btn-secundario w-full">
            <x-icon name="box" class="w-4 h-4" /> Ver el producto
        </a>
    @endif
</div>
