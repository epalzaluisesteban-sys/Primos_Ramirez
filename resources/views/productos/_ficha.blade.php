{{-- Ficha del producto en la ventana flotante del listado (ProductoController::kardex por fetch).
     Variables: $producto, $movimientos (del más reciente al más antiguo), $discrepanciaStock, $existenciaFinal. --}}
@php
    $margen = $producto->porcentajeGanancia();
    $ultimos = $movimientos->take(8);
@endphp
<div class="space-y-5">
    <div class="flex items-center gap-3">
        @if($producto->fotoUrl())
            <img src="{{ $producto->fotoUrl() }}" alt="{{ $producto->nombre }}" class="w-14 h-14 rounded-md object-cover border border-stone-200 dark:border-stone-700 shrink-0">
        @else
            <span class="w-14 h-14 rounded-md bg-emerald-50 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 flex items-center justify-center border border-stone-200 dark:border-stone-700 shrink-0">
                <x-icon name="box" class="w-6 h-6" />
            </span>
        @endif
        <div class="min-w-0 text-xs text-stone-500 dark:text-stone-400 space-y-0.5">
            <p>Código <span class="font-semibold text-stone-700 dark:text-stone-200">#{{ $producto->id }}</span>@if($producto->codigo_barras) · Barras <span class="font-semibold text-stone-700 dark:text-stone-200">{{ $producto->codigo_barras }}</span>@endif</p>
            <p>Categoría <span class="font-semibold text-stone-700 dark:text-stone-200">{{ $producto->categoria->nombre ?? 'Sin categoría' }}</span>@if($producto->marca) · Marca <span class="font-semibold text-stone-700 dark:text-stone-200">{{ $producto->marca }}</span>@endif</p>
            @if($producto->ubicacion)<p>Ubicación <span class="font-semibold text-stone-700 dark:text-stone-200">{{ $producto->ubicacion }}</span></p>@endif
            @unless($producto->activo)<p><span class="insignia-gris">Inactivo: no se vende ni se compra</span></p>@endunless
        </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="caja-suave p-3">
            <p class="cifra-etiqueta">Precio</p>
            <x-precio :usd="$producto->precio" class="font-display text-lg font-bold text-emerald-800 dark:text-emerald-400" />
        </div>
        <div class="caja-suave p-3">
            <p class="cifra-etiqueta">Costo</p>
            <p class="font-display text-lg font-bold text-stone-800 dark:text-stone-100">{{ $producto->precio_compra !== null ? \App\Support\Moneda::usd($producto->precio_compra) : '—' }}</p>
        </div>
        <div class="caja-suave p-3">
            <p class="cifra-etiqueta">Margen</p>
            <p class="font-display text-lg font-bold {{ $margen === null ? 'text-stone-800 dark:text-stone-100' : ($margen >= 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400') }}">{{ $margen === null ? '—' : number_format($margen, 1) . '%' }}</p>
        </div>
        <div class="caja-suave p-3">
            <p class="cifra-etiqueta">Existencia</p>
            <p class="font-display text-lg font-bold {{ $producto->tieneStockCritico() ? 'text-rose-600 dark:text-rose-400' : 'text-stone-800 dark:text-stone-100' }}">
                @cantidad($producto->stock) <span class="text-xs font-normal text-stone-500 dark:text-stone-400">{{ $producto->unidadLabel() }}</span>
            </p>
            <p class="text-[11px] text-stone-500 dark:text-stone-400">Mínimo @cantidad($producto->stock_minimo)</p>
        </div>
    </div>

    @if($discrepanciaStock)
        <div class="aviso-ambar text-xs">
            <x-icon name="alert-triangle" class="w-4 h-4 shrink-0" />
            <span>Los movimientos suman @cantidad($existenciaFinal) y el stock dice @cantidad($producto->stock). Corrígelo con un ajuste de inventario.</span>
        </div>
    @endif

    <div>
        <h3 class="titulo-tarjeta mb-2">Últimos movimientos</h3>
        @if($ultimos->isEmpty())
            <p class="text-sm text-stone-500 dark:text-stone-400">Este producto todavía no tiene entradas ni salidas.</p>
        @else
            <ul class="divide-y divide-stone-200 dark:divide-stone-700 border-y border-stone-200 dark:border-stone-700">
                @foreach($ultimos as $mov)
                    <li class="flex items-center justify-between gap-3 py-2 text-sm">
                        <div class="min-w-0">
                            <p class="font-semibold text-stone-700 dark:text-stone-200 truncate">{{ $mov['origen'] }}</p>
                            <p class="text-[11px] text-stone-500 dark:text-stone-400">{{ $mov['fecha']->format('d/m/Y h:i A') }} · {{ $mov['responsable'] }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="font-bold {{ $mov['es_entrada'] ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">{{ $mov['es_entrada'] ? '+' : '−' }}{{ \App\Support\Cantidad::formato($mov['cantidad']) }}</p>
                            <p class="text-[11px] text-stone-500 dark:text-stone-400">Quedó {{ \App\Support\Cantidad::formato($mov['existencia']) }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
            @if($movimientos->count() > $ultimos->count())
                <p class="ayuda-campo">Se muestran los {{ $ultimos->count() }} más recientes de {{ $movimientos->count() }}. El resto está en el kardex completo.</p>
            @endif
        @endif
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
        <a href="{{ route('productos.kardex', $producto) }}" class="btn-primario">
            <x-icon name="clock" class="w-4 h-4" /> Kardex completo
        </a>
        <a href="{{ route('productos.edit', $producto) }}" class="btn-secundario">
            <x-icon name="edit" class="w-4 h-4" /> Editar
        </a>
        <a href="{{ route('productos.etiqueta', $producto) }}" target="_blank" rel="noopener" class="btn-secundario">
            <x-icon name="printer" class="w-4 h-4" /> Etiqueta
        </a>
    </div>
</div>
