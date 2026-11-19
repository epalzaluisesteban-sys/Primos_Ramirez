@extends('layouts.app')

@section('title', 'Clientes')

@section('content')
    <div class="pagina">

        {{-- El obrero entra para cobrar fiados: ve la lista y las fichas, pero no crea, edita ni exporta --}}
        @php $gestiona = auth()->user()->esEncargadoOSuperior(); @endphp
        <x-encabezado titulo="Clientes" subtitulo="Datos de cada cliente y lo que debe por ventas fiadas. Toca un cliente para ver su cuenta.">
            @if($gestiona)
                @php $filtroEstado = $estado === 'todos' ? [] : ['estado' => $estado]; @endphp
                <x-acciones-exportar :excel="route('clientes.excel', $filtroEstado)" :pdf="route('clientes.pdf', $filtroEstado)" />
                <a href="{{ route('clientes.create') }}" data-abrir-ventana="ventana-cliente" class="btn-primario"><x-icon name="plus" class="w-4 h-4" /> Nuevo cliente</a>
            @endif
        </x-encabezado>

        <div class="grid grid-cols-2 gap-3 sm:gap-4 mb-4 sm:mb-6">
            <div class="tarjeta p-4">
                <p class="cifra-etiqueta">Por cobrar (fiado)</p>
                <x-precio :usd="$totalPorCobrar" class="cifra-valor text-orange-700 dark:text-orange-400" />
            </div>
            <div class="tarjeta p-4">
                <p class="cifra-etiqueta">Clientes que deben</p>
                <p class="cifra-valor">{{ $conteos['con_deuda'] }} <span class="text-sm font-normal text-stone-500 dark:text-stone-400">de {{ $conteos['todos'] }}</span></p>
            </div>
        </div>

        <div class="tarjeta p-0 overflow-hidden">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-4 sm:px-6 py-3 sm:py-4 border-b border-stone-200 dark:border-stone-700">
                <nav class="flex gap-2 overflow-x-auto [scrollbar-width:none] -mx-1 px-1" aria-label="Filtrar clientes">
                    @foreach(\App\Http\Controllers\ClienteController::ESTADOS as $clave => $nombre)
                        <a href="{{ route('clientes.index', $clave === 'todos' ? [] : ['estado' => $clave]) }}"
                           @if($estado === $clave) aria-current="page" @endif
                           class="{{ $estado === $clave ? 'btn-oscuro' : 'btn-secundario' }} btn-sm shrink-0 whitespace-nowrap">
                            {{ $nombre }} <span class="insignia-gris">{{ $conteos[$clave] }}</span>
                        </a>
                    @endforeach
                </nav>
                <div class="relative sm:w-72">
                    <x-icon name="search" class="w-4 h-4 text-stone-400 dark:text-stone-500 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                    <input type="search" id="buscador-clientes" oninput="filtrarTabla('buscador-clientes', 'tabla-clientes')" placeholder="Buscar por nombre o teléfono…" aria-label="Buscar cliente" class="campo pl-10" autocomplete="off">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="tabla" id="tabla-clientes">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th class="hidden md:table-cell">Teléfono</th>
                            <th class="hidden lg:table-cell">Última compra</th>
                            <th class="hidden lg:table-cell text-right">Total comprado</th>
                            <th class="text-right">Debe</th>
                            <th class="hidden sm:table-cell text-right" title="Límite de crédito menos lo que debe">Crédito disponible</th>
                            <th class="text-right"><span class="sr-only">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clientes as $cli)
                            <tr data-ficha="{{ route('clientes.show', $cli) }}" data-titulo="{{ $cli->nombre }}" class="cursor-pointer {{ $cli->activo ? '' : 'opacity-60' }}">
                                <td>
                                    <div class="flex items-center gap-2.5">
                                        @if($cli->fotoUrl())
                                            <img src="{{ $cli->fotoUrl() }}" alt="" class="w-8 h-8 rounded-full object-cover border border-stone-200 dark:border-stone-700 shrink-0">
                                        @else
                                            <span class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 font-bold text-xs flex items-center justify-center border border-stone-200 dark:border-stone-700 shrink-0">
                                                {{ strtoupper(substr($cli->nombre, 0, 1)) }}
                                            </span>
                                        @endif
                                        <div class="min-w-0">
                                            <a href="{{ route('clientes.show', $cli) }}" class="font-bold text-stone-900 dark:text-stone-100 hover:underline" aria-label="Ver cuenta de {{ $cli->nombre }}">{{ $cli->nombre }}</a>
                                            @unless($cli->activo) <span class="insignia-gris ml-1 align-middle">Inactivo</span> @endunless
                                            @if($cli->rif)
                                                <p class="text-[11px] text-stone-500 dark:text-stone-400">{{ $cli->rif }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="hidden md:table-cell text-xs whitespace-nowrap">{{ $cli->telefono ? \App\Support\Contacto::telefono($cli->telefono) : '—' }}</td>
                                <td class="hidden lg:table-cell text-xs">{{ $cli->ventas_max_fecha ? \Carbon\Carbon::parse($cli->ventas_max_fecha)->format('d/m/Y') : 'Sin compras' }}</td>
                                <td class="hidden lg:table-cell text-right font-semibold text-stone-700 dark:text-stone-300">@usd($cli->ventas_sum_total ?? 0)</td>
                                <td class="text-right whitespace-nowrap font-bold {{ $cli->saldo > 0 ? 'text-orange-700 dark:text-orange-400' : 'text-stone-400 dark:text-stone-500' }}">
                                    <x-precio :usd="$cli->saldo" class="items-end" size="small" />
                                    @if($cli->deuda_desde)
                                        <p class="text-[11px] font-normal text-stone-500 dark:text-stone-400">debe desde {{ $cli->deuda_desde->format('d/m/Y') }}</p>
                                    @endif
                                </td>
                                <td class="hidden sm:table-cell text-right whitespace-nowrap">
                                    @if($cli->disponible === null)
                                        <span class="insignia-gris" title="No tiene límite de crédito">Sin límite</span>
                                    @elseif((float) $cli->limite_credito <= 0)
                                        <span class="insignia-gris" title="Su límite de crédito es $0">No se le fía</span>
                                    @elseif($cli->disponible <= 0)
                                        <span class="insignia-roja">Agotado</span>
                                        <p class="text-[11px] text-stone-500 dark:text-stone-400 mt-1">Límite @usd($cli->limite_credito)</p>
                                    @else
                                        <x-precio :usd="$cli->disponible" class="items-end font-bold text-emerald-700 dark:text-emerald-400" size="small" />
                                        <p class="text-[11px] text-stone-500 dark:text-stone-400">de @usd($cli->limite_credito)</p>
                                    @endif
                                </td>
                                <td class="cursor-default" data-acciones>
                                    <div class="flex justify-end items-center gap-2">
                                        <a href="{{ route('clientes.show', $cli->id) }}" class="btn-icono-verde" title="Cobrar abono" aria-label="Cobrar abono a {{ $cli->nombre }}">
                                            <x-icon name="banknote" class="w-4 h-4" />
                                        </a>
                                        @if($gestiona)
                                            <a href="{{ route('clientes.edit', $cli->id) }}" data-editar class="hidden sm:inline-flex btn-icono-ambar" title="Editar" aria-label="Editar {{ $cli->nombre }}">
                                                <x-icon name="edit" class="w-4 h-4" />
                                            </a>
                                        @endif
                                        @if(auth()->user()->esDueno())
                                            <form action="{{ route('clientes.destroy', $cli->id) }}" method="POST" data-confirm="¿Seguro que desea eliminar a {{ $cli->nombre }}? Si tiene ventas o abonos no se puede: desactívalo desde Editar." class="hidden sm:inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icono-rojo" title="Eliminar" aria-label="Eliminar {{ $cli->nombre }}">
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
                                    {{ ['todos' => 'Todavía no hay clientes.', 'con_deuda' => 'Ningún cliente debe nada.', 'sin_deuda' => 'Todos los clientes tienen deuda.'][$estado] }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="px-4 sm:px-6 py-3 text-[11px] text-stone-500 dark:text-stone-400 border-t border-stone-200 dark:border-stone-700">
                Toca un cliente para ver lo que debe, su crédito y sus últimos fiados y abonos. El billete abre su cuenta para cobrarle un abono.
            </p>
        </div>

    </div>

    @if($gestiona)
        <x-ventana id="ventana-cliente" titulo="Nuevo cliente" subtitulo="Regístralo para venderle a crédito (fiado) y llevar su cuenta.">
            <form action="{{ route('clientes.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <input type="hidden" name="_ventana" value="ventana-cliente">
                @include('clientes._form')
                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-1">
                    <button type="button" data-cerrar-ventana class="btn-secundario">Cancelar</button>
                    <button type="submit" class="btn-primario"><x-icon name="check" class="w-4 h-4" /> Guardar cliente</button>
                </div>
            </form>
        </x-ventana>
    @endif
@endsection
