@extends('layouts.app')

@section('title', 'Ventas registradas')

@section('content')
    <div class="pagina">

        <x-encabezado titulo="Ventas registradas" subtitulo="Consulta las ventas por fecha, imprime el ticket, edítalas o anúlalas.">
            <x-acciones-exportar :excel="route('ventas.excel', request()->only(['fecha_inicio', 'fecha_fin']))" />
            <a href="{{ route('ventas.create') }}" class="btn-primario"><x-icon name="plus" class="w-4 h-4" /> Nueva venta</a>
        </x-encabezado>

        <div class="tarjeta mb-6">
            <div class="flex flex-wrap items-end justify-between gap-3 mb-4">
                <h2 class="titulo-tarjeta">Filtrar por fecha</h2>
                <div class="text-right">
                    <span class="cifra-etiqueta block">Total en selección</span>
                    <x-precio :usd="$totalPeriodo" :ves="$totalPeriodoVes" class="items-end font-display text-xl font-bold text-emerald-800 dark:text-emerald-400" />
                </div>
            </div>

            <!-- Formulario de Filtros -->
            <form action="{{ route('ventas.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="etiqueta">Desde</label>
                    <input type="date" name="fecha_inicio" value="{{ request('fecha_inicio') }}" class="campo">
                </div>

                <div>
                    <label class="etiqueta">Hasta</label>
                    <input type="date" name="fecha_fin" value="{{ request('fecha_fin') }}" class="campo">
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="btn-oscuro w-full">
                        <x-icon name="search" class="w-4 h-4" /> Filtrar
                    </button>

                    @if(request('fecha_inicio') || request('fecha_fin'))
                        <a href="{{ route('ventas.index') }}" class="btn-secundario px-3" title="Quitar filtro" aria-label="Quitar filtro">
                            <x-icon name="x" class="w-4 h-4" />
                        </a>
                    @endif
                </div>

                <!-- Botón directo Cierre de Caja Hoy -->
                <div>
                    <a href="{{ route('ventas.index', ['fecha_inicio' => date('Y-m-d'), 'fecha_fin' => date('Y-m-d')]) }}" class="btn-secundario w-full">
                        <x-icon name="calendar" class="w-4 h-4" /> Solo hoy
                    </a>
                </div>
            </form>
        </div>

        <!-- Tabla de Registros de Ventas -->
        <div class="tarjeta">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-stone-600 dark:text-stone-300">
                    <thead class="text-stone-500 dark:text-stone-400 uppercase text-xs border-b border-stone-200 dark:border-stone-700">
                        <tr>
                            <th class="py-3 px-4 font-semibold">N° transacción</th>
                            <th class="py-3 px-4 font-semibold">Fecha/Hora</th>
                            <th class="py-3 px-4 font-semibold">Atendido por</th>
                            <th class="py-3 px-4 font-semibold">Pago</th>
                            <th class="py-3 px-4 font-semibold">Productos</th>
                            <th class="py-3 px-4 font-semibold">Monto total</th>
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
                                        <span class="insignia-naranja">
                                            Fiado @if($v->cliente) — {{ $v->cliente->nombre }} @endif
                                        </span>
                                    @else
                                        <span class="insignia-verde">Contado</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4">
                                    <ul class="list-disc list-inside text-xs text-stone-500 dark:text-stone-400">
                                        @foreach($v->detalles as $det)
                                            <li>{{ $det->producto->nombre ?? 'Producto eliminado' }} (x{{ \App\Support\Cantidad::formato($det->cantidad) }})</li>
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
                                        <span class="insignia-roja">Anulada</span>
                                    @elseif($v->editada)
                                        <span class="insignia-ambar">Editada</span>
                                    @else
                                        <span class="bg-stone-100 text-stone-500 font-bold text-xs px-2.5 py-1 rounded border border-stone-300 dark:bg-stone-700 dark:text-stone-300 dark:border-stone-600">Normal</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div class="flex justify-center items-center gap-1.5">
                                        <a href="{{ route('ventas.pdf', $v->id) }}" target="_blank" class="btn-oscuro gap-1.5 hover:bg-stone-50 text-xs px-3 py-1.5" title="Comprobante PDF">
                                            <x-icon name="download" class="w-3.5 h-3.5" /> PDF
                                        </a>
                                        <a href="{{ route('ventas.ticket', $v->id) }}" target="_blank" class="inline-flex items-center gap-1.5 bg-stone-100 hover:bg-stone-100 dark:bg-stone-700 dark:text-stone-200 dark:hover:bg-stone-600 text-stone-700 text-xs font-semibold px-3 py-1.5 rounded-md transition" title="Ticket para impresora térmica">
                                            <x-icon name="printer" class="w-3.5 h-3.5" /> Ticket
                                        </a>
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if($v->puedeEditarse() && auth()->user()->esEncargadoOSuperior())
                                        <div class="flex justify-center items-center gap-2">
                                            <a href="{{ route('ventas.edit', $v->id) }}" class="p-2 rounded-md bg-amber-50 hover:bg-amber-100 dark:bg-amber-900/20 dark:hover:bg-amber-900/40 text-amber-800 dark:text-amber-300 transition" title="Editar venta">
                                                <x-icon name="edit" class="w-4 h-4" />
                                            </a>
                                            <form action="{{ route('ventas.anular', $v->id) }}" method="POST" data-confirm="¿Anular la venta #{{ $v->id }}? El stock de los productos se devolverá al inventario." class="inline">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" class="p-2 rounded-md bg-rose-50 hover:bg-rose-100 dark:bg-rose-900/20 dark:hover:bg-rose-900/40 text-rose-800 dark:text-rose-300 transition" title="Anular venta">
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
