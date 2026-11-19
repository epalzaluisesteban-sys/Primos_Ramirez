{{-- Ficha de una entrada de mercancía (ventana flotante de Recibir mercancía y página compras.show).
     Variables: $compra (con producto, usuario y proveedorRef). Las compras no se editan: cambiarían el stock,
     el kardex, el costo promedio y la deuda con el proveedor. --}}
@php $credito = $compra->tipo_pago === 'credito'; @endphp
<div class="space-y-5">
    <div class="flex items-center gap-3">
        @if($compra->producto?->fotoUrl())
            <img src="{{ $compra->producto->fotoUrl() }}" alt="{{ $compra->producto->nombre }}" class="w-14 h-14 rounded-md object-cover border border-stone-200 dark:border-stone-700 shrink-0">
        @else
            <span class="w-14 h-14 rounded-md bg-emerald-50 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 flex items-center justify-center border border-stone-200 dark:border-stone-700 shrink-0">
                <x-icon name="inbox-down" class="w-6 h-6" />
            </span>
        @endif
        <div class="min-w-0 text-xs text-stone-500 dark:text-stone-400 space-y-0.5">
            <p class="text-sm font-bold text-stone-800 dark:text-stone-100">{{ $compra->producto->nombre ?? 'Producto eliminado' }}</p>
            <p>Del {{ \Illuminate\Support\Carbon::parse($compra->fecha)->format('d/m/Y') }} · Proveedor <span class="font-semibold text-stone-700 dark:text-stone-200">{{ $compra->nombreProveedor() }}</span></p>
            <p>Registró <span class="font-semibold text-stone-700 dark:text-stone-200">{{ $compra->usuario->name ?? 'Sistema' }}</span> el {{ $compra->created_at->format('d/m/Y h:i A') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="caja-suave p-3">
            <p class="cifra-etiqueta">Cantidad</p>
            <p class="font-display text-lg font-bold text-emerald-800 dark:text-emerald-400">
                +@cantidad($compra->cantidad) <span class="text-xs font-normal text-stone-500 dark:text-stone-400">{{ $compra->producto?->unidadLabel() }}</span>
            </p>
        </div>
        <div class="caja-suave p-3">
            <p class="cifra-etiqueta">Costo unitario</p>
            <p class="font-display text-lg font-bold text-stone-800 dark:text-stone-100">@usd($compra->precio_compra)</p>
        </div>
        <div class="caja-suave p-3">
            <p class="cifra-etiqueta">Total</p>
            <p class="font-display text-lg font-bold text-stone-800 dark:text-stone-100">@usd($compra->total)</p>
        </div>
        <div class="caja-suave p-3">
            <p class="cifra-etiqueta">Pago</p>
            <p class="mt-1"><span class="{{ $credito ? 'insignia-naranja' : 'insignia-gris' }}">{{ $credito ? 'A crédito' : 'De contado' }}</span></p>
            @if($credito)
                <p class="text-[11px] text-stone-500 dark:text-stone-400 mt-1">{{ $compra->vence_el ? 'Vence el ' . $compra->vence_el->format('d/m/Y') : 'Sin fecha de vencimiento' }}</p>
            @endif
        </div>
    </div>

    <p class="ayuda-campo">Una entrada registrada no se cambia: ya sumó al stock y al costo. Si la cantidad estuvo mal, corrige la existencia desde Editar en el producto y queda como ajuste en el kardex.</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
        @if($compra->producto)
            <a href="{{ route('productos.kardex', $compra->producto) }}" class="btn-secundario">
                <x-icon name="box" class="w-4 h-4" /> Ver el producto
            </a>
        @endif
        @if($compra->proveedorRef)
            <a href="{{ route('proveedores.show', $compra->proveedorRef) }}" class="btn-secundario">
                <x-icon name="truck" class="w-4 h-4" /> {{ $credito ? 'Ver lo que se le debe' : 'Ver el proveedor' }}
            </a>
        @endif
    </div>
</div>
