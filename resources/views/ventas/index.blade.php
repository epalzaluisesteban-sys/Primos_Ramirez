@extends('layouts.app')

@section('title', 'Ventas registradas')

@section('content')
    @php $gestiona = auth()->user()->esEncargadoOSuperior(); @endphp
    <div class="pagina">

        @php $filtros = array_filter(request()->only(['fecha_inicio', 'fecha_fin', 'categoria_id'])); @endphp
        <x-encabezado titulo="Ventas registradas" subtitulo="Consulta las ventas por fecha o categoría, mira el detalle, imprime el ticket, edítalas o elimínalas.">
            <x-acciones-exportar :excel="route('ventas.excel', $filtros)" :pdf="route('ventas.listado.pdf', $filtros)" />
            @if($gestiona)
                <a href="{{ route('ventas.papelera') }}" class="btn-secundario">
                    <x-icon name="trash" class="w-4 h-4" /> Papelera @if($enPapelera > 0)<span class="insignia-gris">{{ $enPapelera }}</span>@endif
                </a>
            @endif
            <a href="{{ route('ventas.create') }}" class="btn-primario"><x-icon name="plus" class="w-4 h-4" /> Nueva venta</a>
        </x-encabezado>

        <div class="tarjeta mb-4 sm:mb-6">
            <div class="flex flex-wrap items-end justify-between gap-3 mb-3 sm:mb-4">
                <h2 class="titulo-tarjeta">Filtrar ventas</h2>
                <div class="flex flex-wrap justify-end gap-6 text-right">
                    @if($categoria)
                        <div>
                            <span class="cifra-etiqueta block">Vendido en {{ $categoria->nombre }}</span>
                            <x-precio :usd="$totalCategoria" class="items-end font-display text-xl font-bold text-emerald-800 dark:text-emerald-400" />
                        </div>
                    @endif
                    <div>
                        <span class="cifra-etiqueta block">{{ $categoria ? 'Total de esas ventas' : 'Total en selección' }}</span>
                        <x-precio :usd="$totalPeriodo" :ves="$totalPeriodoVes" class="items-end font-display text-xl font-bold {{ $categoria ? 'text-stone-700 dark:text-stone-200' : 'text-emerald-800 dark:text-emerald-400' }}" />
                    </div>
                </div>
            </div>

            <x-filtros id="filtros-ventas" :activos="count($filtros)">
            <form action="{{ route('ventas.index') }}" method="GET" class="grid grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4 items-end">
                <div>
                    <label class="etiqueta" for="fecha_inicio">Desde</label>
                    <input id="fecha_inicio" type="date" name="fecha_inicio" value="{{ request('fecha_inicio') }}" class="campo">
                </div>

                <div>
                    <label class="etiqueta" for="fecha_fin">Hasta</label>
                    <input id="fecha_fin" type="date" name="fecha_fin" value="{{ request('fecha_fin') }}" class="campo @error('fecha_fin') border-rose-500 @enderror">
                    @error('fecha_fin') <p class="error-campo">{{ $message }}</p> @enderror
                </div>

                <x-selector-categoria :categorias="$categorias" :valor="request('categoria_id')" envoltura="col-span-2 lg:col-span-1" />

                <div class="flex gap-2">
                    <button type="submit" class="btn-oscuro w-full">
                        <x-icon name="search" class="w-4 h-4" /> Filtrar
                    </button>

                    @if($filtros)
                        <a href="{{ route('ventas.index') }}" class="btn-secundario px-3" title="Quitar filtro" aria-label="Quitar filtro">
                            <x-icon name="x" class="w-4 h-4" />
                        </a>
                    @endif
                </div>

                <div>
                    <a href="{{ route('ventas.index', ['fecha_inicio' => date('Y-m-d'), 'fecha_fin' => date('Y-m-d')]) }}" class="btn-secundario w-full">
                        <x-icon name="calendar" class="w-4 h-4" /> Solo hoy
                    </a>
                </div>
            </form>
            </x-filtros>
        </div>

        <div class="tarjeta p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="tabla" id="tabla-ventas">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Fecha</th>
                            <th class="hidden md:table-cell">Cliente</th>
                            <th class="hidden sm:table-cell">Pago</th>
                            <th class="hidden lg:table-cell">Productos</th>
                            <th class="text-right">Total</th>
                            <th class="text-center hidden lg:table-cell">Estado</th>
                            @if($gestiona)
                                <th class="hidden sm:table-cell text-right">Acciones</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 dark:divide-stone-700">
                        @forelse($ventas as $v)
                            @php
                                $primero = $v->detalles->first();
                                $otros = $v->detalles->count() - 1;
                            @endphp
                            <tr data-ficha="{{ route('ventas.show', $v) }}" data-titulo="Venta #{{ $v->id }}" class="cursor-pointer">
                                <td>
                                    <a href="{{ route('ventas.show', $v) }}" class="font-bold text-stone-900 dark:text-stone-100 hover:underline" aria-label="Ver detalle de la venta #{{ $v->id }}">#{{ $v->id }}</a>
                                </td>
                                <td class="text-xs whitespace-nowrap">
                                    {{ $v->fecha->format('d/m/Y') }}
                                    <span class="block text-stone-500 dark:text-stone-400">{{ $v->fecha->format('h:i A') }}<span class="hidden sm:inline"> · {{ $v->usuario->name ?? 'Sistema' }}</span></span>
                                    <span class="md:hidden block text-stone-500 dark:text-stone-400">{{ $v->cliente->nombre ?? 'Mostrador' }}{{ $v->tipo_pago === 'credito' ? ' · fiado' : '' }}</span>
                                </td>
                                <td class="text-xs hidden md:table-cell">{{ $v->cliente->nombre ?? 'Mostrador' }}</td>
                                <td class="text-xs hidden sm:table-cell">
                                    @if($v->tipo_pago === 'credito')
                                        <span class="insignia-naranja">Fiado</span>
                                    @else
                                        <span class="insignia-verde">Contado{{ $v->moneda_pago === 'VES' ? ' en Bs.' : '' }}</span>
                                    @endif
                                </td>
                                <td class="text-xs hidden lg:table-cell">
                                    @if($primero)
                                        {{ $primero->producto->nombre ?? 'Producto eliminado' }} (x{{ \App\Support\Cantidad::formato($primero->cantidad) }})
                                        @if($otros > 0)
                                            <span class="block text-stone-500 dark:text-stone-400">y {{ $otros }} {{ $otros === 1 ? 'producto' : 'productos' }} más</span>
                                        @endif
                                    @endif
                                </td>
                                <td class="text-right whitespace-nowrap text-emerald-800 dark:text-emerald-400">
                                    <x-precio :usd="$v->total" :ves="$v->total_ves" class="items-end" />
                                </td>
                                <td class="text-center hidden lg:table-cell">
                                    @if($v->editada)
                                        <span class="insignia-ambar">Editada</span>
                                    @else
                                        <span class="insignia-gris">Normal</span>
                                    @endif
                                </td>
                                @if($gestiona)
                                    <td class="hidden sm:table-cell cursor-default" data-acciones>
                                        <div class="flex justify-end items-center gap-2">
                                            @if($motivo = $v->motivoNoEditable())
                                                {{-- Se ven siempre; desactivados dicen por qué al tocarlos --}}
                                                <button type="button" data-motivo="{{ $motivo }}" aria-disabled="true" class="btn-icono-gris opacity-50 cursor-not-allowed" title="{{ $motivo }}" aria-label="No se puede editar la venta #{{ $v->id }}">
                                                    <x-icon name="edit" class="w-4 h-4" />
                                                </button>
                                                <button type="button" data-motivo="{{ $motivo }}" aria-disabled="true" class="btn-icono-gris opacity-50 cursor-not-allowed" title="{{ $motivo }}" aria-label="No se puede eliminar la venta #{{ $v->id }}">
                                                    <x-icon name="trash" class="w-4 h-4" />
                                                </button>
                                            @else
                                                <a href="{{ route('ventas.edit', $v) }}" class="btn-icono-ambar" title="Editar venta" aria-label="Editar venta #{{ $v->id }}">
                                                    <x-icon name="edit" class="w-4 h-4" />
                                                </a>
                                                <form action="{{ route('ventas.anular', $v) }}" method="POST" class="inline"
                                                      data-confirm="¿Mandar la venta #{{ $v->id }} a la papelera? La mercancía vuelve al inventario y la venta deja de contar en la caja.">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" class="btn-icono-rojo" title="Eliminar venta" aria-label="Eliminar venta #{{ $v->id }}">
                                                        <x-icon name="trash" class="w-4 h-4" />
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $gestiona ? 8 : 7 }}" class="tabla-vacia">No hay ventas en estas fechas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($gestiona)
                <p class="px-4 py-3 text-[11px] text-stone-500 dark:text-stone-400 border-t border-stone-200 dark:border-stone-700">
                    Toca una venta para ver su detalle e imprimir el ticket o el PDF. Editar y eliminar solo se pueden usar en las ventas de hoy mientras la caja siga abierta. Una venta eliminada va a la papelera: deja de contar en la caja y la mercancía vuelve al inventario.
                </p>
            @endif
        </div>

    </div>

@endsection

@push('scripts')
    <script>
        // Editar y eliminar desactivados: al tocarlos dicen por qué no se pueden usar
        document.getElementById('tabla-ventas').addEventListener('click', function (e) {
            const desactivado = e.target.closest('[data-motivo]');
            if (desactivado) mostrarToast(desactivado.dataset.motivo, 'error');
        });
    </script>
@endpush
