@extends('layouts.app')

@section('title', 'Inventario de Productos')

@section('content')
    <div class="max-w-7xl mx-auto px-4 py-8">

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <div>
                <h2 class="font-display text-xl font-bold text-stone-900 dark:text-stone-100">Inventario General de Productos</h2>
                <p class="text-sm text-stone-500 dark:text-stone-400 mt-0.5">Gestión de precios, niveles de stock e historial de movimientos kardex.</p>
            </div>
            <div class="flex items-center gap-3">
                <x-acciones-exportar :excel="route('productos.excel')" :pdf="route('productos.pdf')" />
                <a href="{{ route('productos.create') }}" class="flex items-center gap-2 bg-emerald-700 hover:bg-emerald-800 text-white font-semibold px-4 py-2.5 rounded-xl text-sm shadow-sm transition">
                    <x-icon name="plus" class="w-4 h-4" /> Nuevo Producto
                </a>
            </div>
        </div>

        <!-- Tarjeta Principal de Tabla -->
        <div class="bg-white dark:bg-stone-800 rounded-2xl shadow-sm border border-stone-200 dark:border-stone-700 p-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-5 gap-4 border-b border-stone-200 dark:border-stone-700 pb-4">
                <h3 class="font-display text-base font-bold text-stone-800 dark:text-stone-100">Listado de Productos</h3>
                <span class="bg-emerald-50 text-emerald-800 border border-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-300 dark:border-emerald-700 text-xs font-bold px-3 py-1.5 rounded-full">
                    Total Ítems: {{ $productos->count() }}
                </span>
            </div>

            <div class="relative mb-4">
                <x-icon name="search" class="w-4 h-4 text-stone-400 dark:text-stone-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
                <input type="text" id="buscador-productos" oninput="filtrarTabla('buscador-productos', 'tabla-productos')" placeholder="Buscar por nombre o categoría..." class="w-full rounded-lg border-stone-300 border pl-10 pr-4 py-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-stone-900 dark:border-stone-600 dark:text-stone-100 dark:placeholder-stone-500">
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-stone-600 dark:text-stone-300" id="tabla-productos">
                    <thead class="text-stone-500 dark:text-stone-400 uppercase text-xs border-b border-stone-200 dark:border-stone-700">
                        <tr>
                            <th class="py-3 px-4 font-semibold">Código</th>
                            <th class="py-3 px-4 font-semibold">Producto</th>
                            <th class="py-3 px-4 font-semibold">Categoría</th>
                            <th class="py-3 px-4 font-semibold text-right">Precio Venta</th>
                            <th class="py-3 px-4 font-semibold text-center">Stock Disponible</th>
                            <th class="py-3 px-4 font-semibold text-center">Vencimiento</th>
                            <th class="py-3 px-4 font-semibold text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 dark:divide-stone-700">
                        @forelse($productos as $prod)
                            <tr class="hover:bg-stone-50 dark:hover:bg-stone-700/40">
                                <td class="py-3 px-4 font-bold text-stone-900 dark:text-stone-100">#{{ $prod->id }}</td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-2.5">
                                        @if($prod->fotoUrl())
                                            <img src="{{ $prod->fotoUrl() }}" alt="{{ $prod->nombre }}" class="w-9 h-9 rounded-lg object-cover border border-stone-200 dark:border-stone-700 shrink-0">
                                        @else
                                            <span class="w-9 h-9 rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 flex items-center justify-center border border-stone-200 dark:border-stone-700 shrink-0">
                                                <x-icon name="box" class="w-4 h-4" />
                                            </span>
                                        @endif
                                        <div>
                                            <p class="font-bold text-stone-800 dark:text-stone-100 text-base">{{ $prod->nombre }}</p>
                                            @if($prod->marca)
                                                <p class="text-[11px] text-stone-400 dark:text-stone-500">{{ $prod->marca }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-xs font-semibold text-stone-600 dark:text-stone-300">
                                    <span class="bg-stone-100 dark:bg-stone-700 dark:text-stone-200 px-2.5 py-1 rounded-md border border-stone-300 dark:border-stone-700">
                                        {{ $prod->categoria->nombre ?? 'Sin Categoría' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right font-extrabold text-emerald-800 dark:text-emerald-400 text-base">
                                    <x-precio :usd="$prod->precio" class="items-end" />
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if($prod->tieneStockCritico())
                                        <span class="inline-flex items-center gap-1 bg-rose-100 text-rose-800 font-extrabold text-xs px-3 py-1 rounded-full border border-rose-300 dark:bg-rose-900/40 dark:text-rose-300 dark:border-rose-700">
                                            <x-icon name="alert-triangle" class="w-3 h-3" /> {{ $prod->stock }} {{ $prod->unidadLabel() }}
                                        </span>
                                    @else
                                        <span class="bg-emerald-100 text-emerald-800 font-bold text-xs px-3 py-1 rounded-full border border-emerald-300 dark:bg-emerald-900/40 dark:text-emerald-300 dark:border-emerald-700">
                                            {{ $prod->stock }} {{ $prod->unidadLabel() }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center text-xs">
                                    @php $dias = $prod->diasParaVencer(); @endphp
                                    @if(is_null($dias))
                                        <span class="text-stone-600 dark:text-stone-300">—</span>
                                    @elseif($dias < 0)
                                        <span class="bg-rose-100 text-rose-800 font-bold px-2.5 py-1 rounded-full border border-rose-300 dark:bg-rose-900/40 dark:text-rose-300 dark:border-rose-700">Vencido</span>
                                    @elseif($dias <= 5)
                                        <span class="bg-amber-100 text-amber-800 font-bold px-2.5 py-1 rounded-full border border-amber-300 dark:bg-amber-900/40 dark:text-amber-300 dark:border-amber-700">{{ $dias }} día(s)</span>
                                    @else
                                        <span class="text-stone-500 dark:text-stone-400">{{ $prod->fecha_vencimiento->format('d/m/Y') }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div class="flex justify-center items-center gap-2">
                                        <a href="{{ route('productos.kardex', $prod->id) }}" class="p-2 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300 dark:hover:bg-indigo-900/60 transition" title="Consultar Kardex">
                                            <x-icon name="clock" class="w-4 h-4" />
                                        </a>

                                        <a href="{{ route('productos.edit', $prod->id) }}" class="p-2 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 dark:hover:bg-amber-900/60 transition" title="Editar">
                                            <x-icon name="edit" class="w-4 h-4" />
                                        </a>

                                        @if(auth()->check() && auth()->user()->esDueno())
                                            <form action="{{ route('productos.destroy', $prod->id) }}" method="POST" data-confirm="¿Seguro que desea eliminar este producto? Se perderá el historial asociado." class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-2 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300 dark:hover:bg-rose-900/60 transition" title="Eliminar">
                                                    <x-icon name="trash" class="w-4 h-4" />
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-10 text-center text-stone-400 dark:text-stone-500">
                                    <x-icon name="carrot" class="w-8 h-8 mx-auto mb-2 text-stone-300 dark:text-stone-600" />
                                    No hay productos registrados en la base de datos aún.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection
