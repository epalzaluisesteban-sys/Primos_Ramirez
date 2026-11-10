@extends('layouts.app')

@section('title', 'Proveedores')

@section('content')
    <div class="pagina">

        <x-encabezado titulo="Proveedores" subtitulo="Quién te surte la mercancía. Se eligen al registrar una compra.">
            <a href="{{ route('proveedores.create') }}" class="btn-primario"><x-icon name="plus" class="w-4 h-4" /> Nuevo proveedor</a>
        </x-encabezado>

        <div class="tarjeta p-0 overflow-hidden">
            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-3 px-6 py-4 border-b border-stone-200 dark:border-stone-700">
                <div class="flex items-center gap-2">
                    <h3 class="titulo-tarjeta">Listado</h3>
                    <span class="insignia-gris">{{ $proveedores->count() }}</span>
                </div>
                <div class="relative sm:w-80">
                    <x-icon name="search" class="w-4 h-4 text-stone-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                    <input type="text" id="buscador-proveedores" oninput="filtrarTabla('buscador-proveedores', 'tabla-proveedores')" placeholder="Buscar por nombre, contacto o teléfono…" class="campo pl-10">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="tabla" id="tabla-proveedores">
                    <thead>
                        <tr>
                            <th>Proveedor</th>
                            <th>Teléfono</th>
                            <th class="text-center">Compras</th>
                            <th class="text-center">Estado</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($proveedores as $prov)
                            <tr class="{{ $prov->activo ? '' : 'opacity-60' }}">
                                <td>
                                    <a href="{{ route('proveedores.show', $prov->id) }}" class="font-bold text-stone-900 dark:text-stone-100 hover:text-emerald-700 dark:hover:text-emerald-400">{{ $prov->nombre }}</a>
                                    <p class="text-xs text-stone-500 dark:text-stone-400">{{ $prov->contacto ?: 'Sin persona de contacto' }}</p>
                                </td>
                                <td class="text-xs whitespace-nowrap">{{ $prov->telefono ? \App\Support\Contacto::telefono($prov->telefono) : '—' }}</td>
                                <td class="text-center font-semibold">{{ $prov->compras_count }}</td>
                                <td class="text-center">
                                    @if($prov->activo)
                                        <span class="insignia-verde">Activo</span>
                                    @else
                                        <span class="insignia-gris">Inactivo</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex justify-end items-center gap-2">
                                        <a href="{{ route('proveedores.show', $prov->id) }}" class="btn-icono-gris" title="Ver sus compras">
                                            <x-icon name="truck" class="w-4 h-4" />
                                        </a>
                                        @if($prov->whatsappUrl())
                                            <a href="{{ $prov->whatsappUrl() }}" target="_blank" rel="noopener" class="btn-icono-verde" title="Escribir por WhatsApp">
                                                <x-icon name="whatsapp" class="w-4 h-4" />
                                            </a>
                                        @endif
                                        <a href="{{ route('proveedores.edit', $prov->id) }}" class="btn-icono-ambar" title="Editar">
                                            <x-icon name="edit" class="w-4 h-4" />
                                        </a>

                                        @if($prov->compras_count > 0 || !$prov->activo)
                                            {{-- Con compras no se puede borrar: se desactiva y deja de salir en compras nuevas --}}
                                            <form action="{{ route('proveedores.activo', $prov->id) }}" method="POST" class="inline"
                                                  @if($prov->activo) data-confirm="¿Desactivar a {{ $prov->nombre }}? No aparecerá en compras nuevas, pero sus compras anteriores se conservan." @endif>
                                                @csrf
                                                @method('PATCH')
                                                @if($prov->activo)
                                                    <button type="submit" class="btn-icono-gris" title="Desactivar (se conserva su historial)">
                                                        <x-icon name="ban" class="w-4 h-4" />
                                                    </button>
                                                @else
                                                    <button type="submit" class="btn-icono-verde" title="Volver a activar">
                                                        <x-icon name="check-circle" class="w-4 h-4" />
                                                    </button>
                                                @endif
                                            </form>
                                        @endif

                                        @if($prov->compras_count === 0 && auth()->user()->esDueno())
                                            <form action="{{ route('proveedores.destroy', $prov->id) }}" method="POST" class="inline"
                                                  data-confirm="¿Eliminar a {{ $prov->nombre }}? No tiene compras registradas, así que se borrará por completo.">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icono-rojo" title="Eliminar (no tiene compras)">
                                                    <x-icon name="trash" class="w-4 h-4" />
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="tabla-vacia">
                                    Todavía no hay proveedores.
                                    <a href="{{ route('proveedores.create') }}" class="font-semibold text-emerald-700 dark:text-emerald-400 hover:underline">Registra el primero</a>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($proveedores->isNotEmpty())
                <p class="px-6 py-3 border-t border-stone-200 dark:border-stone-700 text-[11px] text-stone-500 dark:text-stone-400 flex flex-wrap gap-x-4 gap-y-1">
                    <span class="inline-flex items-center gap-1"><x-icon name="ban" class="w-3.5 h-3.5" /> Desactivar: para quien ya tiene compras registradas.</span>
                    @if(auth()->user()->esDueno())
                        <span class="inline-flex items-center gap-1"><x-icon name="trash" class="w-3.5 h-3.5" /> Eliminar: solo si nunca se le compró nada.</span>
                    @endif
                </p>
            @endif
        </div>

    </div>
@endsection
