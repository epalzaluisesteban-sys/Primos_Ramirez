@extends('layouts.app')

@section('title', 'Proveedores')

@section('content')
    <div class="pagina">

        <x-encabezado titulo="Proveedores" subtitulo="Quién te surte la mercancía. Toca uno para ver lo que se le debe y sus compras.">
            <a href="{{ route('proveedores.deudas') }}" class="btn-secundario"><x-icon name="banknote" class="w-4 h-4" /> Lo que debemos</a>
            <a href="{{ route('proveedores.create') }}" data-abrir-ventana="ventana-proveedor" class="btn-primario"><x-icon name="plus" class="w-4 h-4" /> Nuevo proveedor</a>
        </x-encabezado>

        <div class="tarjeta p-0 overflow-hidden">
            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-3 px-4 sm:px-6 py-3 sm:py-4 border-b border-stone-200 dark:border-stone-700">
                <div class="flex items-center gap-2">
                    <h3 class="titulo-tarjeta">Listado</h3>
                    <span class="insignia-gris">{{ $proveedores->count() }}</span>
                </div>
                <div class="relative sm:w-80">
                    <x-icon name="search" class="w-4 h-4 text-stone-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                    <input type="text" id="buscador-proveedores" oninput="filtrarTabla('buscador-proveedores', 'tabla-proveedores')" placeholder="Buscar por nombre, lo que distribuye o teléfono…" class="campo pl-10">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="tabla" id="tabla-proveedores">
                    <thead>
                        <tr>
                            <th>Proveedor</th>
                            <th class="hidden md:table-cell">Distribuye</th>
                            <th class="hidden lg:table-cell">Teléfono</th>
                            <th class="hidden sm:table-cell text-center">Compras</th>
                            <th class="text-right">Le debemos</th>
                            <th class="hidden md:table-cell text-center">Estado</th>
                            <th class="text-right"><span class="sr-only">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($proveedores as $prov)
                            <tr data-ficha="{{ route('proveedores.show', $prov) }}" data-titulo="{{ $prov->nombre }}" class="cursor-pointer {{ $prov->activo ? '' : 'opacity-60' }}">
                                <td>
                                    <a href="{{ route('proveedores.show', $prov->id) }}" class="font-bold text-stone-900 dark:text-stone-100 hover:underline" aria-label="Ver ficha de {{ $prov->nombre }}">{{ $prov->nombre }}</a>
                                    <p class="text-xs text-stone-500 dark:text-stone-400">{{ $prov->contacto ?: 'Sin persona de contacto' }}</p>
                                </td>
                                <td class="hidden md:table-cell text-xs">
                                    {{ $prov->categorias->pluck('nombre')->join(', ') ?: '—' }}
                                    @if($prov->da_credito)
                                        <span class="insignia-naranja ml-1">Crédito{{ $prov->dias_credito ? " {$prov->dias_credito} días" : '' }}</span>
                                    @endif
                                </td>
                                <td class="hidden lg:table-cell text-xs whitespace-nowrap">{{ $prov->telefono ? \App\Support\Contacto::telefono($prov->telefono) : '—' }}</td>
                                <td class="hidden sm:table-cell text-center font-semibold">{{ $prov->compras_count }}</td>
                                @php $debe = round((float) $prov->comprado_credito - (float) $prov->pagado, 2); @endphp
                                <td class="text-right whitespace-nowrap {{ $debe > 0 ? 'font-bold text-orange-700 dark:text-orange-400' : 'text-stone-400 dark:text-stone-500' }}">
                                    {{ $debe > 0 ? \App\Support\Moneda::usd($debe) : '—' }}
                                </td>
                                <td class="hidden md:table-cell text-center">
                                    @if($prov->activo)
                                        <span class="insignia-verde">Activo</span>
                                    @else
                                        <span class="insignia-gris">Inactivo</span>
                                    @endif
                                </td>
                                <td class="cursor-default" data-acciones>
                                    <div class="flex justify-end items-center gap-2">
                                        @if($prov->whatsappUrl())
                                            <a href="{{ $prov->whatsappUrl() }}" target="_blank" rel="noopener" class="btn-icono-verde" title="Escribir por WhatsApp">
                                                <x-icon name="whatsapp" class="w-4 h-4" />
                                            </a>
                                        @endif
                                        <a href="{{ route('proveedores.edit', $prov->id) }}" data-editar class="hidden sm:inline-flex btn-icono-ambar" title="Editar" aria-label="Editar {{ $prov->nombre }}">
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
                                <td colspan="7" class="tabla-vacia">
                                    Todavía no hay proveedores.
                                    <a href="{{ route('proveedores.create') }}" data-abrir-ventana="ventana-proveedor" class="font-semibold text-emerald-700 dark:text-emerald-400 hover:underline">Registra el primero</a>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($proveedores->isNotEmpty())
                <p class="px-4 sm:px-6 py-3 border-t border-stone-200 dark:border-stone-700 text-[11px] text-stone-500 dark:text-stone-400 flex flex-wrap gap-x-4 gap-y-1">
                    <span>Toca un proveedor para ver su ficha.</span>
                    <span class="inline-flex items-center gap-1"><x-icon name="ban" class="w-3.5 h-3.5" /> Desactivar: para quien ya tiene compras registradas.</span>
                    @if(auth()->user()->esDueno())
                        <span class="inline-flex items-center gap-1"><x-icon name="trash" class="w-3.5 h-3.5" /> Eliminar: solo si nunca se le compró nada.</span>
                    @endif
                </p>
            @endif
        </div>

    </div>

    <x-ventana id="ventana-proveedor" titulo="Nuevo proveedor" subtitulo="Después podrás elegirlo al registrar una compra." ancho="sm:max-w-2xl">
        <form action="{{ route('proveedores.store') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="_ventana" value="ventana-proveedor">
            @include('proveedores._form')
            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-1">
                <button type="button" data-cerrar-ventana class="btn-secundario">Cancelar</button>
                <button type="submit" class="btn-primario"><x-icon name="check" class="w-4 h-4" /> Guardar proveedor</button>
            </div>
        </form>
    </x-ventana>
@endsection
