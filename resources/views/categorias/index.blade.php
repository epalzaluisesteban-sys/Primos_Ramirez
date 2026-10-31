@extends('layouts.app')

@section('title', 'Categorías')

@section('content')
    <div class="max-w-7xl mx-auto px-4 py-8">

        <div class="mb-6">
            <h2 class="font-display text-xl font-bold text-stone-900">Gestión de Categorías</h2>
            <p class="text-sm text-stone-500 mt-0.5">Organiza el inventario en grupos de productos.</p>
        </div>

        @if(session('success'))
            <div class="flex items-center gap-2.5 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl mb-6 text-sm">
                <x-icon name="check" class="w-4 h-4 text-emerald-600" /> {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="flex items-center gap-2.5 bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl mb-6 text-sm">
                <x-icon name="alert-triangle" class="w-4 h-4 shrink-0 text-rose-500" /> {{ $errors->first() }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- Formulario para Nueva Categoría -->
            <div class="bg-white rounded-2xl shadow-sm border border-stone-200 p-6 h-fit">
                <h3 class="font-display text-base font-bold text-stone-800 mb-4 flex items-center gap-2">
                    <x-icon name="tag" class="w-4 h-4 text-emerald-600" /> Nueva Categoría
                </h3>

                <form action="{{ route('categorias.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-stone-600 uppercase mb-1.5">Imagen (Opcional)</label>
                        <input type="file" name="imagen" accept="image/*" class="w-full text-sm text-stone-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:text-emerald-700 file:font-semibold hover:file:bg-emerald-100">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-stone-600 uppercase mb-1.5">Nombre</label>
                        <input type="text" name="nombre" placeholder="Ej. Hortalizas, Granos..." required class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-stone-600 uppercase mb-1.5">Descripción (Opcional)</label>
                        <textarea name="descripcion" rows="3" class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-emerald-500 focus:border-emerald-500"></textarea>
                    </div>

                    <button type="submit" class="w-full flex items-center justify-center gap-2 bg-emerald-700 hover:bg-emerald-800 text-white font-semibold py-2.5 px-4 rounded-xl shadow-sm border border-stone-200 transition text-sm">
                        <x-icon name="plus" class="w-4 h-4" /> Guardar Categoría
                    </button>
                </form>
            </div>

            <!-- Listado de Categorías -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-stone-200 p-6">
                <h3 class="font-display text-base font-bold text-stone-800 mb-4">Categorías Registradas</h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-stone-600">
                        <thead class="text-stone-500 uppercase text-xs border-b border-stone-200">
                            <tr>
                                <th class="py-3 px-4 font-semibold">Nombre</th>
                                <th class="py-3 px-4 font-semibold">Descripción</th>
                                <th class="py-3 px-4 font-semibold">Productos</th>
                                <th class="py-3 px-4 font-semibold text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            @forelse($categorias as $cat)
                                <tr class="hover:bg-stone-50">
                                    <td class="py-3 px-4 font-bold text-stone-900">
                                        <div class="flex items-center gap-2.5">
                                            @if($cat->imagenUrl())
                                                <img src="{{ $cat->imagenUrl() }}" alt="{{ $cat->nombre }}" class="w-9 h-9 rounded-lg object-cover border border-stone-200 shrink-0">
                                            @else
                                                <span class="w-9 h-9 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center border border-stone-200 shrink-0">
                                                    <x-icon name="tag" class="w-4 h-4" />
                                                </span>
                                            @endif
                                            {{ $cat->nombre }}
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">{{ $cat->descripcion ?? 'Sin descripción' }}</td>
                                    <td class="py-3 px-4">
                                        <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-bold">
                                            {{ $cat->productos_count }} ítems
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <div class="flex justify-center items-center gap-2">
                                            <a href="{{ route('categorias.edit', $cat->id) }}" class="p-2 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-800 transition inline-flex" title="Editar">
                                                <x-icon name="edit" class="w-4 h-4" />
                                            </a>
                                            <form action="{{ route('categorias.destroy', $cat->id) }}" method="POST" data-confirm="¿Desea eliminar esta categoría?" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-2 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-800 transition inline-flex" title="Eliminar">
                                                    <x-icon name="trash" class="w-4 h-4" />
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-stone-500">No hay categorías registradas aún.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>
@endsection
