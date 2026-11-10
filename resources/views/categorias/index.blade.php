@extends('layouts.app')

@section('title', 'Categorías')

@section('content')
    <div class="pagina">

        <x-encabezado titulo="Categorías" subtitulo="Grupos para ordenar los productos (frutas, verduras, granos…)." />

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

            <!-- Nueva categoría -->
            <div class="tarjeta">
                <h3 class="titulo-tarjeta mb-4">Nueva categoría
                </h3>

                <form action="{{ route('categorias.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    @include('categorias._form')

                    <button type="submit" class="btn-primario w-full">
                        <x-icon name="plus" class="w-4 h-4" /> Guardar categoría
                    </button>
                </form>
            </div>

            <!-- Listado -->
            <div class="tarjeta lg:col-span-2 p-0 overflow-hidden">
                <div class="flex items-center gap-2 px-6 py-4 border-b border-stone-200 dark:border-stone-700">
                    <h3 class="titulo-tarjeta">Categorías registradas</h3>
                    <span class="insignia-gris">{{ $categorias->count() }}</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="tabla">
                        <thead>
                            <tr>
                                <th>Categoría</th>
                                <th class="text-center">Productos</th>
                                <th class="text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categorias as $cat)
                                <tr>
                                    <td>
                                        <div class="flex items-center gap-3">
                                            @if($cat->imagenUrl())
                                                <img src="{{ $cat->imagenUrl() }}" alt="{{ $cat->nombre }}" class="w-10 h-10 rounded-md object-cover border border-stone-200 dark:border-stone-700 shrink-0">
                                            @else
                                                <span class="w-10 h-10 rounded-md bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 flex items-center justify-center shrink-0">
                                                    <x-icon name="tag" class="w-4 h-4" />
                                                </span>
                                            @endif
                                            <div class="min-w-0">
                                                <p class="font-bold text-stone-900 dark:text-stone-100">{{ $cat->nombre }}</p>
                                                <p class="text-xs text-stone-500 dark:text-stone-400 truncate max-w-xs">{{ $cat->descripcion ?: 'Sin descripción' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if($cat->productos_count > 0)
                                            <span class="insignia-azul">{{ $cat->productos_count }} {{ $cat->productos_count === 1 ? 'producto' : 'productos' }}</span>
                                        @else
                                            <span class="insignia-gris">Vacía</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="flex justify-end items-center gap-2">
                                            <a href="{{ route('categorias.edit', $cat->id) }}" class="btn-icono-ambar" title="Editar">
                                                <x-icon name="edit" class="w-4 h-4" />
                                            </a>
                                            @if(auth()->user()->esDueno())
                                                @if($cat->productos_count === 0)
                                                    <form action="{{ route('categorias.destroy', $cat->id) }}" method="POST" class="inline"
                                                          data-confirm="¿Eliminar la categoría «{{ $cat->nombre }}»? No tiene productos, así que se borrará por completo.">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn-icono-rojo" title="Eliminar (está vacía)">
                                                            <x-icon name="trash" class="w-4 h-4" />
                                                        </button>
                                                    </form>
                                                @else
                                                    {{-- Con productos no se puede borrar: se explica en vez de mostrar un botón que fallaría --}}
                                                    <span class="btn-icono-gris cursor-help opacity-60" title="Para eliminarla, primero mueve sus {{ $cat->productos_count }} productos a otra categoría">
                                                        <x-icon name="lock" class="w-4 h-4" />
                                                    </span>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="tabla-vacia">Todavía no hay categorías. Crea la primera con el formulario.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if(auth()->user()->esDueno() && $categorias->isNotEmpty())
                    <p class="px-6 py-3 border-t border-stone-200 dark:border-stone-700 text-[11px] text-stone-500 dark:text-stone-400 flex flex-wrap gap-x-4 gap-y-1">
                        <span class="inline-flex items-center gap-1"><x-icon name="trash" class="w-3.5 h-3.5" /> Solo se pueden eliminar las categorías vacías.</span>
                        <span class="inline-flex items-center gap-1"><x-icon name="lock" class="w-3.5 h-3.5" /> Con productos: muévelos primero a otra categoría.</span>
                    </p>
                @endif
            </div>

        </div>

    </div>
@endsection
