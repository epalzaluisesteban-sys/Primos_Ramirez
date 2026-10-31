@extends('layouts.app')

@section('title', 'Proveedores')

@section('content')
    <div class="max-w-7xl mx-auto px-4 py-8">

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <div>
                <h2 class="font-display text-xl font-bold text-stone-900">Proveedores</h2>
                <p class="text-sm text-stone-500 mt-0.5">Registro de proveedores para vincular en el módulo de Compras.</p>
            </div>
            <a href="{{ route('proveedores.create') }}" class="flex items-center gap-2 bg-emerald-700 hover:bg-emerald-800 text-white font-semibold px-4 py-2.5 rounded-xl text-sm shadow-sm transition">
                <x-icon name="plus" class="w-4 h-4" /> Nuevo Proveedor
            </a>
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

        <div class="bg-white rounded-2xl shadow-sm border border-stone-200 p-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-5 gap-4 border-b border-stone-200 pb-4">
                <h3 class="font-display text-base font-bold text-stone-800">Listado de Proveedores</h3>
                <span class="bg-emerald-50 text-emerald-800 border border-emerald-200 text-xs font-bold px-3 py-1.5 rounded-full">
                    Total: {{ $proveedores->count() }}
                </span>
            </div>

            <div class="relative mb-4">
                <x-icon name="search" class="w-4 h-4 text-stone-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                <input type="text" id="buscador-proveedores" oninput="filtrarTabla('buscador-proveedores', 'tabla-proveedores')" placeholder="Buscar por nombre, contacto o teléfono..." class="w-full rounded-lg border-stone-300 border pl-10 pr-4 py-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-emerald-500 focus:border-emerald-500">
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-stone-600" id="tabla-proveedores">
                    <thead class="text-stone-500 uppercase text-xs border-b border-stone-200">
                        <tr>
                            <th class="py-3 px-4 font-semibold">Proveedor</th>
                            <th class="py-3 px-4 font-semibold">Contacto</th>
                            <th class="py-3 px-4 font-semibold">Teléfono</th>
                            <th class="py-3 px-4 font-semibold text-center">Compras Registradas</th>
                            <th class="py-3 px-4 font-semibold text-center">Estado</th>
                            <th class="py-3 px-4 font-semibold text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @forelse($proveedores as $prov)
                            <tr class="hover:bg-stone-50">
                                <td class="py-3 px-4 font-bold text-stone-900">{{ $prov->nombre }}</td>
                                <td class="py-3 px-4 text-xs">{{ $prov->contacto ?: '—' }}</td>
                                <td class="py-3 px-4 text-xs">{{ $prov->telefono ?: '—' }}</td>
                                <td class="py-3 px-4 text-center font-semibold">{{ $prov->compras_count }}</td>
                                <td class="py-3 px-4 text-center">
                                    @if($prov->activo)
                                        <span class="bg-emerald-100 text-emerald-800 font-bold text-xs px-3 py-1 rounded-full border border-emerald-300">Activo</span>
                                    @else
                                        <span class="bg-stone-100 text-stone-500 font-bold text-xs px-3 py-1 rounded-full border border-stone-300">Inactivo</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div class="flex justify-center items-center gap-2">
                                        <a href="{{ route('proveedores.show', $prov->id) }}" class="p-2 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 transition" title="Ver historial de compras">
                                            <x-icon name="truck" class="w-4 h-4" />
                                        </a>
                                        @if($prov->whatsappUrl())
                                            <a href="{{ $prov->whatsappUrl() }}" target="_blank" rel="noopener" class="p-2 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 transition" title="Chatear por WhatsApp">
                                                <x-icon name="whatsapp" class="w-4 h-4" />
                                            </a>
                                        @endif
                                        <a href="{{ route('proveedores.edit', $prov->id) }}" class="p-2 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-800 transition" title="Editar">
                                            <x-icon name="edit" class="w-4 h-4" />
                                        </a>
                                        @if(auth()->user()->esDueno())
                                            <form action="{{ route('proveedores.destroy', $prov->id) }}" method="POST" data-confirm="¿Seguro que desea eliminar este proveedor?" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-2 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-800 transition" title="Eliminar">
                                                    <x-icon name="trash" class="w-4 h-4" />
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-stone-500">No hay proveedores registrados aún.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection
