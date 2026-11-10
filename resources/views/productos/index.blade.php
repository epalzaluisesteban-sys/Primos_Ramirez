@extends('layouts.app')

@section('title', 'Productos')

@section('content')
    <div class="pagina">

        <x-encabezado titulo="Productos" subtitulo="Precios, existencias y kardex. Usa los filtros para ver qué falta, qué se vence y qué conviene comprar.">
            <x-acciones-exportar :excel="route('productos.excel')" :pdf="route('productos.pdf')" />
            <a href="{{ route('productos.create') }}" class="btn-primario"><x-icon name="plus" class="w-4 h-4" /> Nuevo producto</a>
        </x-encabezado>

        <!-- Tarjeta Principal de Tabla -->
        <div class="tarjeta">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-4">
                <h3 class="titulo-tarjeta">Listado de productos</h3>
                <span class="insignia-verde">
                    Mostrando: {{ $productos->count() }}
                </span>
            </div>

            {{-- Filtros: aquí están las alertas de stock (la campana enlaza directo a cada uno) --}}
            @php
                $estiloFiltro = [
                    'alertas' => ['icono' => 'bell', 'activo' => 'bg-rose-600 border-rose-600 text-white', 'cuenta' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300'],
                    'agotados' => ['icono' => 'ban', 'activo' => 'bg-rose-600 border-rose-600 text-white', 'cuenta' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300'],
                    'stock_bajo' => ['icono' => 'alert-triangle', 'activo' => 'bg-rose-500 border-rose-500 text-white', 'cuenta' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300'],
                    'vencimiento' => ['icono' => 'clock', 'activo' => 'bg-amber-500 border-amber-500 text-white', 'cuenta' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300'],
                    'comprar' => ['icono' => 'inbox-down', 'activo' => 'bg-blue-600 border-blue-600 text-white', 'cuenta' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300'],
                ];
                $estiloNeutro = ['icono' => null, 'activo' => 'bg-stone-800 border-stone-800 text-white dark:bg-stone-100 dark:border-stone-100 dark:text-stone-900', 'cuenta' => 'bg-stone-100 text-stone-600 dark:bg-stone-700 dark:text-stone-300'];
            @endphp
            <nav class="flex flex-wrap gap-2 mb-2 no-print" aria-label="Filtrar productos">
                @foreach(\App\Services\AlertasInventarioService::FILTROS as $clave => $nombre)
                    @php
                        $estilo = $estiloFiltro[$clave] ?? $estiloNeutro;
                        $esActivo = $filtro === $clave;
                    @endphp
                    <a href="{{ route('productos.index', $clave === 'todos' ? [] : ['filtro' => $clave]) }}"
                       @if($esActivo) aria-current="page" @endif
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded border text-xs font-semibold transition
                              {{ $esActivo ? $estilo['activo'] : 'bg-white border-stone-300 text-stone-700 hover:bg-stone-100 dark:bg-stone-800 dark:border-stone-600 dark:text-stone-200 dark:hover:bg-stone-700' }}">
                        @if($estilo['icono']) <x-icon name="{{ $estilo['icono'] }}" class="w-3.5 h-3.5" /> @endif
                        {{ $nombre }}
                        <span class="min-w-5 px-1.5 py-0.5 rounded text-[10px] font-bold {{ $esActivo ? 'bg-white/25 text-current' : ($conteos[$clave] > 0 ? $estilo['cuenta'] : 'bg-stone-100 text-stone-400 dark:bg-stone-700 dark:text-stone-500') }}">{{ $conteos[$clave] }}</span>
                    </a>
                @endforeach
            </nav>
            <p class="ayuda-campo mb-4 pb-4 border-b border-stone-200 dark:border-stone-700">{{ \App\Services\AlertasInventarioService::AYUDAS[$filtro] }}</p>

            <div class="relative mb-4">
                <x-icon name="search" class="w-4 h-4 text-stone-400 dark:text-stone-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
                <input type="text" id="buscador-productos" oninput="filtrarTabla('buscador-productos', 'tabla-productos')" placeholder="Buscar por nombre o categoría..." class="campo pl-10">
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-stone-600 dark:text-stone-300" id="tabla-productos">
                    <thead class="text-stone-500 dark:text-stone-400 uppercase text-xs border-b border-stone-200 dark:border-stone-700">
                        <tr>
                            <th class="py-3 px-4 font-semibold">Código</th>
                            <th class="py-3 px-4 font-semibold">Producto</th>
                            <th class="py-3 px-4 font-semibold">Categoría</th>
                            <th class="py-3 px-4 font-semibold text-right">Precio venta</th>
                            <th class="py-3 px-4 font-semibold text-center">Stock disponible</th>
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
                                            <img src="{{ $prod->fotoUrl() }}" alt="{{ $prod->nombre }}" class="w-9 h-9 rounded-md object-cover border border-stone-200 dark:border-stone-700 shrink-0">
                                        @else
                                            <span class="w-9 h-9 rounded-md bg-emerald-50 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 flex items-center justify-center border border-stone-200 dark:border-stone-700 shrink-0">
                                                <x-icon name="box" class="w-4 h-4" />
                                            </span>
                                        @endif
                                        <div>
                                            <p class="font-bold text-stone-800 dark:text-stone-100 text-base">
                                                {{ $prod->nombre }}
                                                @unless($prod->activo)
                                                    <span class="ml-1 align-middle text-[10px] font-bold uppercase px-1.5 py-0.5 rounded bg-stone-200 text-stone-600 dark:bg-stone-700 dark:text-stone-300">Inactivo</span>
                                                @endunless
                                            </p>
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
                                        <span class="insignia-roja gap-1">
                                            <x-icon name="alert-triangle" class="w-3 h-3" /> {{ \App\Support\Cantidad::formato($prod->stock) }} {{ $prod->unidadLabel() }}
                                        </span>
                                    @else
                                        <span class="insignia-verde">
                                            {{ \App\Support\Cantidad::formato($prod->stock) }} {{ $prod->unidadLabel() }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center text-xs">
                                    @php $dias = $prod->diasParaVencer(); @endphp
                                    @if(is_null($dias))
                                        <span class="text-stone-600 dark:text-stone-300">—</span>
                                    @elseif($dias < 0)
                                        <span class="insignia-roja">Vencido</span>
                                    @elseif($dias <= 5)
                                        <span class="insignia-ambar">{{ $dias }} día(s)</span>
                                    @else
                                        <span class="text-stone-500 dark:text-stone-400">{{ $prod->fecha_vencimiento->format('d/m/Y') }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div class="flex justify-center items-center gap-2">
                                        @if(isset($idsComprar[$prod->id]))
                                            <a href="{{ route('compras.index', ['producto' => $prod->id]) }}" class="btn-icono-verde" title="Comprar: registrar la mercancía que llegue" aria-label="Comprar {{ $prod->nombre }}">
                                                <x-icon name="inbox-down" class="w-4 h-4" />
                                            </a>
                                        @endif
                                        @if($prod->activo && ($prod->diasParaVencer() ?? 1) < 0)
                                            <a href="{{ route('mermas.index', ['producto' => $prod->id]) }}" class="btn-icono-rojo" title="Vencido: registrarlo como pérdida" aria-label="Registrar pérdida de {{ $prod->nombre }}">
                                                <x-icon name="package-x" class="w-4 h-4" />
                                            </a>
                                        @endif
                                        <a href="{{ route('productos.kardex', $prod->id) }}" class="p-2 rounded-md bg-indigo-50 hover:bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300 dark:hover:bg-indigo-900/60 transition" title="Consultar Kardex">
                                            <x-icon name="clock" class="w-4 h-4" />
                                        </a>

                                        <a href="{{ route('productos.edit', $prod->id) }}" class="p-2 rounded-md bg-amber-50 hover:bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 dark:hover:bg-amber-900/60 transition" title="Editar">
                                            <x-icon name="edit" class="w-4 h-4" />
                                        </a>

                                        @if(auth()->check() && auth()->user()->esDueno())
                                            <form action="{{ route('productos.destroy', $prod->id) }}" method="POST" data-confirm="¿Seguro que desea eliminar este producto? Solo se puede eliminar si no tiene compras, ventas ni pérdidas registradas; si las tiene, desactívalo desde Editar." class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-2 rounded-md bg-rose-50 hover:bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300 dark:hover:bg-rose-900/60 transition" title="Eliminar">
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
                                    @if($filtro === 'todos')
                                        <x-icon name="carrot" class="w-8 h-8 mx-auto mb-2 text-stone-300 dark:text-stone-600" />
                                        No hay productos registrados en la base de datos aún.
                                    @else
                                        <x-icon name="check-circle" class="w-8 h-8 mx-auto mb-2 text-emerald-500" />
                                        Nada en "{{ \App\Services\AlertasInventarioService::FILTROS[$filtro] }}" por ahora.
                                        <a href="{{ route('productos.index') }}" class="font-semibold text-emerald-700 dark:text-emerald-400 hover:underline">Ver todos los productos</a>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection
