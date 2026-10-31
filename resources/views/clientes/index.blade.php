@extends('layouts.app')

@section('title', 'Clientes')

@section('content')
    <div class="max-w-7xl mx-auto px-4 py-8">

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <div>
                <h2 class="font-display text-xl font-bold text-stone-900 dark:text-stone-100">Clientes y Cuentas por Cobrar</h2>
                <p class="text-sm text-stone-500 dark:text-stone-400 mt-0.5">Gestiona tus clientes y el saldo fiado de cada uno.</p>
            </div>
            <div class="flex items-center gap-3">
                <x-acciones-exportar :excel="route('clientes.excel')" :pdf="route('clientes.pdf')" />
                <a href="{{ route('clientes.create') }}" class="flex items-center gap-2 bg-emerald-700 hover:bg-emerald-800 text-white font-semibold px-4 py-2.5 rounded-xl text-sm shadow-sm transition">
                    <x-icon name="plus" class="w-4 h-4" /> Nuevo Cliente
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">
            <div class="bg-white dark:bg-stone-800 rounded-2xl shadow-sm border border-stone-200 dark:border-stone-700 p-6 border-t-4 border-orange-500">
                <p class="text-xs font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wide">Total por Cobrar (Fiado)</p>
                <x-precio :usd="$totalPorCobrar" class="mt-1 font-display text-2xl font-bold text-orange-700 dark:text-orange-400" />
            </div>
            <div class="bg-white dark:bg-stone-800 rounded-2xl shadow-sm border border-stone-200 dark:border-stone-700 p-6 border-t-4 border-emerald-500">
                <p class="text-xs font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wide">Clientes Registrados</p>
                <p class="font-display text-2xl font-bold text-stone-800 dark:text-stone-100 mt-1">{{ $clientes->count() }}</p>
            </div>
        </div>

        <div class="bg-white dark:bg-stone-800 rounded-2xl shadow-sm border border-stone-200 dark:border-stone-700 p-6">
            <div class="relative mb-4">
                <x-icon name="search" class="w-4 h-4 text-stone-400 dark:text-stone-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
                <input type="text" id="buscador-clientes" oninput="filtrarTabla('buscador-clientes', 'tabla-clientes')" placeholder="Buscar por nombre o teléfono..." class="w-full rounded-lg border-stone-300 border pl-10 pr-4 py-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-stone-900 dark:border-stone-600 dark:text-stone-100 dark:placeholder-stone-500">
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-stone-600 dark:text-stone-300" id="tabla-clientes">
                    <thead class="text-stone-500 dark:text-stone-400 uppercase text-xs border-b border-stone-200 dark:border-stone-700">
                        <tr>
                            <th class="py-3 px-4 font-semibold">Cliente</th>
                            <th class="py-3 px-4 font-semibold">Teléfono</th>
                            <th class="py-3 px-4 font-semibold">Última Compra</th>
                            <th class="py-3 px-4 font-semibold text-right">Total Comprado</th>
                            <th class="py-3 px-4 font-semibold text-right">Saldo Pendiente</th>
                            <th class="py-3 px-4 font-semibold text-center">Estado</th>
                            <th class="py-3 px-4 font-semibold text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 dark:divide-stone-700">
                        @forelse($clientes as $cli)
                            <tr class="hover:bg-stone-50/60 dark:hover:bg-stone-700/40">
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-2.5">
                                        @if($cli->fotoUrl())
                                            <img src="{{ $cli->fotoUrl() }}" alt="{{ $cli->nombre }}" class="w-8 h-8 rounded-full object-cover border border-stone-200 dark:border-stone-700 shrink-0">
                                        @else
                                            <span class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 font-bold text-xs flex items-center justify-center border border-stone-200 dark:border-stone-700 shrink-0">
                                                {{ strtoupper(substr($cli->nombre, 0, 1)) }}
                                            </span>
                                        @endif
                                        <div>
                                            <p class="font-bold text-stone-900 dark:text-stone-100">{{ $cli->nombre }}</p>
                                            @if($cli->rif)
                                                <p class="text-[11px] text-stone-400 dark:text-stone-500">{{ $cli->rif }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-xs">{{ $cli->telefono ?: '—' }}</td>
                                <td class="py-3 px-4 text-xs">{{ $cli->ventas_max_fecha ? \Carbon\Carbon::parse($cli->ventas_max_fecha)->format('d/m/Y') : 'Sin compras' }}</td>
                                <td class="py-3 px-4 text-right font-semibold text-stone-700 dark:text-stone-300">${{ number_format($cli->ventas_sum_total ?? 0, 2) }}</td>
                                <td class="py-3 px-4 text-right font-bold {{ $cli->saldo > 0 ? 'text-orange-700 dark:text-orange-400' : 'text-stone-400 dark:text-stone-500' }}">
                                    <x-precio :usd="$cli->saldo" class="items-end" size="small" />
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if($cli->activo)
                                        <span class="bg-emerald-100 text-emerald-800 font-bold text-xs px-3 py-1 rounded-full border border-emerald-300 dark:bg-emerald-900/40 dark:text-emerald-300 dark:border-emerald-700">Activo</span>
                                    @else
                                        <span class="bg-stone-100 text-stone-500 font-bold text-xs px-3 py-1 rounded-full border border-stone-300 dark:bg-stone-700 dark:text-stone-300 dark:border-stone-600">Inactivo</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div class="flex justify-center items-center gap-2">
                                        <a href="{{ route('clientes.show', $cli->id) }}" class="p-2 rounded-lg bg-orange-50 hover:bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400 dark:hover:bg-orange-900/50 transition" title="Ver cuenta / Registrar abono">
                                            <x-icon name="banknote" class="w-4 h-4" />
                                        </a>
                                        <a href="{{ route('clientes.edit', $cli->id) }}" class="p-2 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 dark:hover:bg-amber-900/50 transition" title="Editar">
                                            <x-icon name="edit" class="w-4 h-4" />
                                        </a>
                                        @if(auth()->user()->esDueno())
                                            <form action="{{ route('clientes.destroy', $cli->id) }}" method="POST" data-confirm="¿Seguro que desea eliminar este cliente?" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-2 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400 dark:hover:bg-rose-900/50 transition" title="Eliminar">
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
                                    <x-icon name="users" class="w-8 h-8 mx-auto mb-2 text-stone-300 dark:text-stone-600" />
                                    No hay clientes registrados aún.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection
