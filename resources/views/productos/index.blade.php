@extends('layouts.app')

@section('title', 'Productos')

@section('content')
    <div class="pagina">

        <x-encabezado titulo="Productos" subtitulo="Precios, existencias y kardex. Usa los filtros para ver qué falta, qué se vence y qué conviene comprar.">
            @php $filtrosActivos = array_filter(['filtro' => $filtro === 'todos' ? null : $filtro, 'categoria_id' => $categoria?->id]); @endphp
            <x-acciones-exportar :excel="route('productos.excel', $filtrosActivos)" :pdf="route('productos.pdf', $filtrosActivos)" />
            <a href="{{ route('productos.create') }}" data-abrir-ventana="ventana-producto" class="btn-primario"><x-icon name="plus" class="w-4 h-4" /> Nuevo producto</a>
        </x-encabezado>

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
            <nav class="flex sm:flex-wrap gap-2 mb-2 overflow-x-auto [scrollbar-width:none] -mx-1 px-1 no-print" aria-label="Filtrar productos">
                @foreach(\App\Services\AlertasInventarioService::FILTROS as $clave => $nombre)
                    @php
                        $estilo = $estiloFiltro[$clave] ?? $estiloNeutro;
                        $esActivo = $filtro === $clave;
                    @endphp
                    <a href="{{ route('productos.index', array_filter(['filtro' => $clave === 'todos' ? null : $clave, 'categoria_id' => $categoria?->id])) }}"
                       @if($esActivo) aria-current="page" @endif
                       class="shrink-0 whitespace-nowrap inline-flex items-center gap-1.5 px-3 py-1.5 rounded border text-xs font-semibold transition
                              {{ $esActivo ? $estilo['activo'] : 'bg-white border-stone-300 text-stone-700 hover:bg-stone-100 dark:bg-stone-800 dark:border-stone-600 dark:text-stone-200 dark:hover:bg-stone-700' }}">
                        @if($estilo['icono']) <x-icon name="{{ $estilo['icono'] }}" class="w-3.5 h-3.5" /> @endif
                        {{ $nombre }}
                        <span class="min-w-5 px-1.5 py-0.5 rounded text-[10px] font-bold {{ $esActivo ? 'bg-white/25 text-current' : ($conteos[$clave] > 0 ? $estilo['cuenta'] : 'bg-stone-100 text-stone-400 dark:bg-stone-700 dark:text-stone-500') }}">{{ $conteos[$clave] }}</span>
                    </a>
                @endforeach
            </nav>
            <p class="ayuda-campo mb-4 pb-4 border-b border-stone-200 dark:border-stone-700">{{ \App\Services\AlertasInventarioService::AYUDAS[$filtro] }}</p>

            <div class="grid grid-cols-1 sm:grid-cols-[16rem_minmax(0,1fr)] gap-4 mb-4">
                <form method="GET" action="{{ route('productos.index') }}">
                    @if($filtro !== 'todos') <input type="hidden" name="filtro" value="{{ $filtro }}"> @endif
                    <x-selector-categoria :categorias="$categorias" :valor="$categoria?->id" id="productos-categoria" onchange="this.form.submit()" />
                    <noscript><button type="submit" class="btn-secundario mt-2">Ver</button></noscript>
                </form>
                <div>
                    <label class="etiqueta" for="buscador-productos">Buscar</label>
                    <div class="relative">
                        <x-icon name="search" class="w-4 h-4 text-stone-400 dark:text-stone-500 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                        <input type="search" id="buscador-productos" oninput="filtrarTabla('buscador-productos', 'tabla-productos')" placeholder="Nombre, marca o código…" class="campo pl-10" autocomplete="off">
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto -mx-4">
                <table class="tabla" id="tabla-productos">
                    <thead>
                        <tr>
                            <th class="hidden md:table-cell">Código</th>
                            <th>Producto</th>
                            <th class="hidden lg:table-cell">Categoría</th>
                            <th class="text-right">Precio</th>
                            <th class="text-center">Existencia</th>
                            <th class="hidden md:table-cell text-center">Vencimiento</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($productos as $prod)
                            <tr data-ficha="{{ route('productos.kardex', $prod) }}" data-titulo="{{ $prod->nombre }}" class="cursor-pointer {{ $prod->activo ? '' : 'opacity-60' }}">
                                <td class="hidden md:table-cell font-bold text-stone-900 dark:text-stone-100">#{{ $prod->id }}</td>
                                <td>
                                    <div class="flex items-center gap-2.5">
                                        @if($prod->fotoUrl())
                                            <img src="{{ $prod->fotoUrl() }}" alt="" class="w-9 h-9 rounded-md object-cover border border-stone-200 dark:border-stone-700 shrink-0">
                                        @else
                                            <span class="w-9 h-9 rounded-md bg-emerald-50 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 flex items-center justify-center border border-stone-200 dark:border-stone-700 shrink-0">
                                                <x-icon name="box" class="w-4 h-4" />
                                            </span>
                                        @endif
                                        <div>
                                            <a href="{{ route('productos.kardex', $prod) }}" class="font-bold text-stone-800 dark:text-stone-100 hover:underline" aria-label="Ver ficha de {{ $prod->nombre }}">{{ $prod->nombre }}</a>
                                            @unless($prod->activo)
                                                <span class="insignia-gris ml-1 align-middle">Inactivo</span>
                                            @endunless
                                            @if($prod->marca)
                                                <p class="text-[11px] text-stone-500 dark:text-stone-400">{{ $prod->marca }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="hidden lg:table-cell"><span class="insignia-gris">{{ $prod->categoria->nombre ?? 'Sin categoría' }}</span></td>
                                <td class="text-right whitespace-nowrap text-emerald-800 dark:text-emerald-400">
                                    <x-precio :usd="$prod->precio" class="items-end" />
                                </td>
                                <td class="text-center whitespace-nowrap">
                                    @if($prod->tieneStockCritico())
                                        <span class="insignia-roja gap-1">
                                            <x-icon name="alert-triangle" class="w-3 h-3" /> @cantidad($prod->stock) {{ $prod->unidadLabel() }}
                                        </span>
                                    @else
                                        <span class="insignia-verde">@cantidad($prod->stock) {{ $prod->unidadLabel() }}</span>
                                    @endif
                                </td>
                                @php $dias = $prod->diasParaVencer(); @endphp
                                <td class="hidden md:table-cell text-center text-xs whitespace-nowrap">
                                    @if(is_null($dias))
                                        <span class="text-stone-500 dark:text-stone-400">—</span>
                                    @elseif($dias < 0)
                                        <span class="insignia-roja">Vencido</span>
                                    @elseif($dias <= 5)
                                        <span class="insignia-ambar">{{ $dias }} día(s)</span>
                                    @else
                                        <span class="text-stone-500 dark:text-stone-400">{{ $prod->fecha_vencimiento->format('d/m/Y') }}</span>
                                    @endif
                                </td>
                                <td class="cursor-default" data-acciones>
                                    <div class="flex justify-end items-center gap-2">
                                        @if(isset($idsComprar[$prod->id]))
                                            <a href="{{ route('compras.index', ['producto' => $prod->id]) }}" class="btn-icono-verde" title="Comprar: registrar la mercancía que llegue" aria-label="Comprar {{ $prod->nombre }}">
                                                <x-icon name="inbox-down" class="w-4 h-4" />
                                            </a>
                                        @endif
                                        @if($prod->activo && ($dias ?? 1) < 0)
                                            <a href="{{ route('mermas.index', ['producto' => $prod->id]) }}" class="btn-icono-rojo" title="Vencido: registrarlo como pérdida" aria-label="Registrar pérdida de {{ $prod->nombre }}">
                                                <x-icon name="package-x" class="w-4 h-4" />
                                            </a>
                                        @endif
                                        <a href="{{ route('productos.edit', $prod->id) }}" data-editar class="hidden sm:inline-flex btn-icono-ambar" title="Editar" aria-label="Editar {{ $prod->nombre }}">
                                            <x-icon name="edit" class="w-4 h-4" />
                                        </a>
                                        @if(auth()->user()->esDueno())
                                            <form action="{{ route('productos.destroy', $prod->id) }}" method="POST" data-confirm="¿Seguro que desea eliminar este producto? Solo se puede eliminar si no tiene compras, ventas ni pérdidas registradas; si las tiene, desactívalo desde Editar." class="hidden sm:inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icono-rojo" title="Eliminar" aria-label="Eliminar {{ $prod->nombre }}">
                                                    <x-icon name="trash" class="w-4 h-4" />
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="tabla-vacia">
                                    @if($filtro === 'todos')
                                        Todavía no hay productos. <a href="{{ route('productos.create') }}" data-abrir-ventana="ventana-producto" class="font-semibold text-emerald-700 dark:text-emerald-400 hover:underline">Registra el primero</a>.
                                    @else
                                        Nada en "{{ \App\Services\AlertasInventarioService::FILTROS[$filtro] }}" por ahora.
                                        <a href="{{ route('productos.index') }}" class="font-semibold text-emerald-700 dark:text-emerald-400 hover:underline">Ver todos los productos</a>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="text-[11px] text-stone-500 dark:text-stone-400 pt-3 mt-1 border-t border-stone-200 dark:border-stone-700">
                Toca un producto para ver su ficha: precio, costo, existencia y últimos movimientos, con el kardex completo y la etiqueta.
            </p>
        </div>

    </div>


    <x-ventana id="ventana-producto" titulo="Nuevo producto" subtitulo="Agrégalo al inventario con su precio y la existencia con que empieza." ancho="sm:max-w-2xl">
        <form action="{{ route('productos.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="hidden" name="_ventana" value="ventana-producto">
            @include('productos._form')
            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-1">
                <button type="button" data-cerrar-ventana class="btn-secundario">Cancelar</button>
                <button type="submit" class="btn-primario"><x-icon name="check" class="w-4 h-4" /> Guardar producto</button>
            </div>
        </form>
    </x-ventana>
@endsection
