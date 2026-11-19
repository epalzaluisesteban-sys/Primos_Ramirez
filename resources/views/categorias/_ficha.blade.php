{{-- Ficha de la categoría (ventana flotante del listado y página categorias.show).
     Variables: $categoria (con proveedores), $productos (activos primero, por nombre). --}}
<div class="space-y-5">
    <div class="flex items-center gap-3">
        @if($categoria->imagenUrl())
            <img src="{{ $categoria->imagenUrl() }}" alt="{{ $categoria->nombre }}" class="w-14 h-14 rounded-md object-cover border border-stone-200 dark:border-stone-700 shrink-0">
        @else
            <span class="w-14 h-14 rounded-md bg-emerald-50 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 flex items-center justify-center border border-stone-200 dark:border-stone-700 shrink-0">
                <x-icon name="tag" class="w-6 h-6" />
            </span>
        @endif
        <div class="min-w-0 text-xs text-stone-500 dark:text-stone-400 space-y-0.5">
            <p class="text-sm text-stone-700 dark:text-stone-200">{{ $categoria->descripcion ?: 'Sin descripción' }}</p>
            <p>Distribuye:
                <span class="font-semibold text-stone-700 dark:text-stone-200">{{ $categoria->proveedores->pluck('nombre')->join(', ') ?: 'ningún proveedor la tiene marcada' }}</span>
            </p>
        </div>
    </div>

    <div>
        <h3 class="titulo-tarjeta mb-2">Productos ({{ $productos->count() }})</h3>
        @if($productos->isEmpty())
            <p class="text-sm text-stone-500 dark:text-stone-400">Esta categoría todavía no tiene productos.</p>
        @else
            <ul class="divide-y divide-stone-200 dark:divide-stone-700 border-y border-stone-200 dark:border-stone-700">
                @foreach($productos as $producto)
                    <li class="flex items-center justify-between gap-3 py-2 text-sm {{ $producto->activo ? '' : 'opacity-60' }}">
                        <div class="min-w-0">
                            <a href="{{ route('productos.kardex', $producto) }}" class="font-semibold text-stone-700 dark:text-stone-200 hover:underline truncate block">{{ $producto->nombre }}@if($producto->marca) <span class="font-normal text-stone-500 dark:text-stone-400">· {{ $producto->marca }}</span>@endif</a>
                            <p class="text-[11px] text-stone-500 dark:text-stone-400">Precio @usd($producto->precio)@unless($producto->activo) · Inactivo @endunless</p>
                        </div>
                        <p class="shrink-0 text-right font-bold {{ $producto->activo && $producto->tieneStockCritico() ? 'text-rose-600 dark:text-rose-400' : 'text-stone-800 dark:text-stone-100' }}">
                            @cantidad($producto->stock) <span class="text-xs font-normal text-stone-500 dark:text-stone-400">{{ $producto->unidadLabel() }}</span>
                        </p>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
        <a href="{{ route('productos.index', ['categoria_id' => $categoria->id]) }}" class="btn-primario">
            <x-icon name="box" class="w-4 h-4" /> Ver en Productos
        </a>
        <a href="{{ route('categorias.edit', $categoria) }}" data-editar class="btn-secundario">
            <x-icon name="edit" class="w-4 h-4" /> Editar
        </a>
    </div>
</div>
