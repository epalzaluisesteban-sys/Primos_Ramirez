@extends('layouts.app')

@section('title', 'Categorías')

@section('content')
    <div class="pagina">

        <x-encabezado titulo="Categorías" subtitulo="Grupos para ordenar los productos (frutas, verduras, granos…). Toca una para ver sus productos.">
            <button type="button" data-abrir-ventana="ventana-categoria" class="btn-primario"><x-icon name="plus" class="w-4 h-4" /> Nueva categoría</button>
        </x-encabezado>

        <div>
            <div class="tarjeta p-0 overflow-hidden">
                <div class="flex items-center gap-2 px-4 sm:px-6 py-3 sm:py-4 border-b border-stone-200 dark:border-stone-700">
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
                                <tr data-ficha="{{ route('categorias.show', $cat) }}" data-titulo="{{ $cat->nombre }}" class="cursor-pointer">
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
                                                <a href="{{ route('categorias.show', $cat) }}" class="font-bold text-stone-900 dark:text-stone-100 hover:underline" aria-label="Ver ficha de {{ $cat->nombre }}">{{ $cat->nombre }}</a>
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
                                    <td class="cursor-default" data-acciones>
                                        <div class="flex justify-end items-center gap-2">
                                            <a href="{{ route('categorias.edit', $cat->id) }}" data-editar class="btn-icono-ambar" title="Editar" aria-label="Editar {{ $cat->nombre }}">
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
                                    <td colspan="3" class="tabla-vacia">Todavía no hay categorías. <button type="button" data-abrir-ventana="ventana-categoria" class="font-semibold text-emerald-700 dark:text-emerald-400 hover:underline">Crea la primera</button>.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if(auth()->user()->esDueno() && $categorias->isNotEmpty())
                    <p class="px-4 sm:px-6 py-3 border-t border-stone-200 dark:border-stone-700 text-[11px] text-stone-500 dark:text-stone-400 flex flex-wrap gap-x-4 gap-y-1">
                        <span class="inline-flex items-center gap-1"><x-icon name="trash" class="w-3.5 h-3.5" /> Solo se pueden eliminar las categorías vacías.</span>
                        <span class="inline-flex items-center gap-1"><x-icon name="lock" class="w-3.5 h-3.5" /> Con productos: muévelos primero a otra categoría.</span>
                    </p>
                @endif
            </div>

        </div>

    </div>

    <x-ventana id="ventana-categoria" titulo="Nueva categoría" subtitulo="Después podrás elegirla al registrar un producto.">
        <form action="{{ route('categorias.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="hidden" name="_ventana" value="ventana-categoria">
            @include('categorias._form')
            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-1">
                <button type="button" data-cerrar-ventana class="btn-secundario">Cancelar</button>
                <button type="submit" class="btn-primario"><x-icon name="check" class="w-4 h-4" /> Guardar categoría</button>
            </div>
        </form>
    </x-ventana>
@endsection
