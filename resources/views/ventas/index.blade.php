@extends('layouts.app')

@section('title', 'Historial de Ventas')

@section('content')
    <div class="max-w-7xl mx-auto px-4 py-8">

        <!-- Encabezado + Filtros -->
        <div class="bg-white dark:bg-stone-800 rounded-2xl shadow-sm border border-stone-200 dark:border-stone-700 p-6 mb-8">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-stone-200 dark:border-stone-700 pb-5 mb-5">
                <div>
                    <h2 class="font-display text-xl font-bold text-stone-900 dark:text-stone-100">Historial de Ventas</h2>
                    <p class="text-sm text-stone-500 dark:text-stone-400 mt-0.5">Consulta transacciones por fecha y descarga comprobantes.</p>
                </div>

                <div class="flex items-center gap-3">
                    <div class="bg-stone-50 dark:bg-stone-900/60 border border-stone-300 dark:border-stone-700 px-4 py-2.5 rounded-xl text-right">
                        <span class="text-[11px] text-stone-500 dark:text-stone-400 block uppercase font-bold tracking-wide">Total en selección</span>
                        <x-precio :usd="$totalPeriodo" :ves="$totalPeriodoVes" class="items-end font-display text-xl font-extrabold text-emerald-800 dark:text-emerald-400" />
                    </div>
                    <x-acciones-exportar :excel="route('ventas.excel', request()->only(['fecha_inicio', 'fecha_fin']))" />
                    <a href="{{ route('ventas.create') }}" class="flex items-center gap-2 bg-emerald-700 hover:bg-emerald-800 text-white font-semibold px-4 py-2.5 rounded-xl text-sm shadow-sm transition no-print">
                        <x-icon name="plus" class="w-4 h-4" /> Nueva Venta
                    </a>
                </div>
            </div>

            <!-- Formulario de Filtros -->
            <form action="{{ route('ventas.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="block text-xs font-semibold text-stone-600 dark:text-stone-300 uppercase mb-1.5">Fecha Desde</label>
                    <input type="date" name="fecha_inicio" value="{{ request('fecha_inicio') }}" class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 dark:bg-stone-900 dark:border-stone-600 dark:text-stone-100 dark:placeholder-stone-500 focus:ring-emerald-500 focus:border-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-stone-600 dark:text-stone-300 uppercase mb-1.5">Fecha Hasta</label>
                    <input type="date" name="fecha_fin" value="{{ request('fecha_fin') }}" class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 dark:bg-stone-900 dark:border-stone-600 dark:text-stone-100 dark:placeholder-stone-500 focus:ring-emerald-500 focus:border-emerald-500">
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="flex items-center justify-center gap-1.5 w-full bg-stone-800 hover:bg-stone-50 text-white font-semibold py-2.5 px-4 rounded-lg transition text-sm">
                        <x-icon name="search" class="w-4 h-4" /> Filtrar
                    </button>

                    @if(request('fecha_inicio') || request('fecha_fin'))
                        <a href="{{ route('ventas.index') }}" class="bg-stone-100 hover:bg-stone-100 dark:bg-stone-700 dark:text-stone-200 dark:hover:bg-stone-600 text-stone-800 font-semibold py-2.5 px-3 rounded-lg transition text-sm flex items-center justify-center">
                            <x-icon name="x" class="w-4 h-4" />
                        </a>
                    @endif
                </div>

                <!-- Botón directo Cierre de Caja Hoy -->
                <div>
                    <a href="{{ route('ventas.index', ['fecha_inicio' => date('Y-m-d'), 'fecha_fin' => date('Y-m-d')]) }}" class="w-full flex items-center justify-center gap-1.5 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/20 dark:hover:bg-blue-900/40 text-blue-800 dark:text-blue-300 font-semibold py-2.5 px-4 rounded-lg transition text-sm border border-blue-100 dark:border-blue-800">
                        <x-icon name="calendar" class="w-4 h-4" /> Cierre de Hoy
                    </a>
                </div>
            </form>
        </div>

        <!-- Tabla de Registros de Ventas -->
        <div class="bg-white dark:bg-stone-800 rounded-2xl shadow-sm border border-stone-200 dark:border-stone-700 p-6">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-stone-600 dark:text-stone-300">
                    <thead class="text-stone-500 dark:text-stone-400 uppercase text-xs border-b border-stone-200 dark:border-stone-700">
                        <tr>
                            <th class="py-3 px-4 font-semibold">N° Transacción</th>
                            <th class="py-3 px-4 font-semibold">Fecha/Hora</th>
                            <th class="py-3 px-4 font-semibold">Atendido Por</th>
                            <th class="py-3 px-4 font-semibold">Pago</th>
                            <th class="py-3 px-4 font-semibold">Productos</th>
                            <th class="py-3 px-4 font-semibold">Monto Total</th>
                            <th class="py-3 px-4 font-semibold text-center">Estado</th>
                            <th class="py-3 px-4 font-semibold text-center">Comprobante</th>
                            <th class="py-3 px-4 font-semibold text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 dark:divide-stone-700">
                        @forelse($ventas as $v)
                            <tr class="hover:bg-stone-50 dark:hover:bg-stone-700/40 {{ $v->anulada ? 'opacity-60' : '' }}">
                                <td class="py-3 px-4 font-bold text-stone-900 dark:text-stone-100">#{{ $v->id }}</td>
                                <td class="py-3 px-4 text-xs text-stone-500 dark:text-stone-400">{{ $v->fecha }}</td>
                                <td class="py-3 px-4 text-xs">{{ $v->usuario->name ?? 'Sistema' }}</td>
                                <td class="py-3 px-4 text-xs">
                                    @if($v->tipo_pago === 'credito')
                                        <span class="bg-orange-100 text-orange-800 font-bold text-xs px-2.5 py-1 rounded-full border border-orange-300 dark:bg-orange-900/40 dark:text-orange-300 dark:border-orange-700">
                                            Fiado @if($v->cliente) — {{ $v->cliente->nombre }} @endif
                                        </span>
                                    @else
                                        <span class="bg-emerald-100 text-emerald-800 font-bold text-xs px-2.5 py-1 rounded-full border border-emerald-300 dark:bg-emerald-900/40 dark:text-emerald-300 dark:border-emerald-700">Contado</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4">
                                    <ul class="list-disc list-inside text-xs text-stone-500 dark:text-stone-400">
                                        @foreach($v->detalles as $det)
                                            <li>{{ $det->producto->nombre ?? 'Producto eliminado' }} (x{{ $det->cantidad }})</li>
                                        @endforeach
                                    </ul>
                                </td>
                                <td class="py-3 px-4 font-bold text-emerald-800 dark:text-emerald-400 text-base">
                                    <x-precio :usd="$v->total" :ves="$v->total_ves" />
                                    @if($v->tipo_pago !== 'credito' && $v->moneda_pago === 'VES')
                                        <span class="block text-[10px] font-semibold text-stone-500 dark:text-stone-400 mt-0.5">Cobrado en Bs.</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if($v->anulada)
                                        <span class="bg-rose-100 text-rose-800 font-bold text-xs px-2.5 py-1 rounded-full border border-rose-300 dark:bg-rose-900/40 dark:text-rose-300 dark:border-rose-700">Anulada</span>
                                    @elseif($v->editada)
                                        <span class="bg-amber-100 text-amber-800 font-bold text-xs px-2.5 py-1 rounded-full border border-amber-300 dark:bg-amber-900/40 dark:text-amber-300 dark:border-amber-700">Editada</span>
                                    @else
                                        <span class="bg-stone-100 text-stone-500 font-bold text-xs px-2.5 py-1 rounded-full border border-stone-300 dark:bg-stone-700 dark:text-stone-300 dark:border-stone-600">Normal</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div class="flex justify-center items-center gap-1.5">
                                        <a href="{{ route('ventas.pdf', $v->id) }}" target="_blank" class="inline-flex items-center gap-1.5 bg-stone-800 hover:bg-stone-50 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition" title="Comprobante PDF">
                                            <x-icon name="download" class="w-3.5 h-3.5" /> PDF
                                        </a>
                                        <a href="{{ route('ventas.ticket', $v->id) }}" target="_blank" class="inline-flex items-center gap-1.5 bg-stone-100 hover:bg-stone-100 dark:bg-stone-700 dark:text-stone-200 dark:hover:bg-stone-600 text-stone-700 text-xs font-semibold px-3 py-1.5 rounded-lg transition" title="Ticket para impresora térmica">
                                            <x-icon name="printer" class="w-3.5 h-3.5" /> Ticket
                                        </a>
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if($v->puedeEditarse() && auth()->user()->esEncargadoOSuperior())
                                        <div class="flex justify-center items-center gap-2">
                                            <a href="{{ route('ventas.edit', $v->id) }}" class="p-2 rounded-lg bg-amber-50 hover:bg-amber-100 dark:bg-amber-900/20 dark:hover:bg-amber-900/40 text-amber-800 dark:text-amber-300 transition" title="Editar venta">
                                                <x-icon name="edit" class="w-4 h-4" />
                                            </a>
                                            <form action="{{ route('ventas.anular', $v->id) }}" method="POST" data-confirm="¿Anular la venta #{{ $v->id }}? El stock de los productos se devolverá al inventario." class="inline">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" class="p-2 rounded-lg bg-rose-50 hover:bg-rose-100 dark:bg-rose-900/20 dark:hover:bg-rose-900/40 text-rose-800 dark:text-rose-300 transition" title="Anular venta">
                                                    <x-icon name="ban" class="w-4 h-4" />
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-xs text-stone-300 dark:text-stone-600">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-6 text-center text-stone-500 dark:text-stone-400">No se encontraron ventas en el rango de fechas seleccionado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection
