@extends('layouts.app')

@section('title', 'Ventas registradas')

@section('content')
    @php $gestiona = auth()->user()->esEncargadoOSuperior(); @endphp
    <div class="pagina">

        <x-encabezado titulo="Ventas registradas" subtitulo="Consulta las ventas por fecha, mira el detalle, imprime el ticket, edítalas o elimínalas.">
            <x-acciones-exportar :excel="route('ventas.excel', request()->only(['fecha_inicio', 'fecha_fin']))" />
            @if($gestiona)
                <a href="{{ route('ventas.papelera') }}" class="btn-secundario">
                    <x-icon name="trash" class="w-4 h-4" /> Papelera @if($enPapelera > 0)<span class="insignia-gris">{{ $enPapelera }}</span>@endif
                </a>
            @endif
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

            <form action="{{ route('ventas.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="etiqueta" for="fecha_inicio">Desde</label>
                    <input id="fecha_inicio" type="date" name="fecha_inicio" value="{{ request('fecha_inicio') }}" class="campo">
                </div>

                <div>
                    <label class="etiqueta" for="fecha_fin">Hasta</label>
                    <input id="fecha_fin" type="date" name="fecha_fin" value="{{ request('fecha_fin') }}" class="campo">
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

                <div>
                    <a href="{{ route('ventas.index', ['fecha_inicio' => date('Y-m-d'), 'fecha_fin' => date('Y-m-d')]) }}" class="btn-secundario w-full">
                        <x-icon name="calendar" class="w-4 h-4" /> Solo hoy
                    </a>
                </div>
            </form>
        </div>

        <div class="tarjeta p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="tabla" id="tabla-ventas">
                    <thead>
                        <tr>
                            <th class="py-3 px-4">N°</th>
                            <th class="py-3 px-4">Fecha</th>
                            <th class="py-3 px-4">Cliente</th>
                            <th class="py-3 px-4">Pago</th>
                            <th class="py-3 px-4">Productos</th>
                            <th class="py-3 px-4 text-right">Total</th>
                            <th class="py-3 px-4 text-center">Estado</th>
                            @if($gestiona)
                                <th class="py-3 px-4 text-right">Acciones</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 dark:divide-stone-700">
                        @forelse($ventas as $v)
                            @php
                                $primero = $v->detalles->first();
                                $otros = $v->detalles->count() - 1;
                            @endphp
                            <tr data-url="{{ route('ventas.show', $v) }}" data-titulo="Venta #{{ $v->id }}" class="cursor-pointer hover:bg-stone-50 dark:hover:bg-stone-700/40">
                                <td class="py-3 px-4">
                                    <a href="{{ route('ventas.show', $v) }}" class="font-bold text-stone-900 dark:text-stone-100 hover:underline" aria-label="Ver detalle de la venta #{{ $v->id }}">#{{ $v->id }}</a>
                                </td>
                                <td class="py-3 px-4 text-xs whitespace-nowrap">
                                    {{ $v->fecha->format('d/m/Y') }}
                                    <span class="block text-stone-500 dark:text-stone-400">{{ $v->fecha->format('h:i A') }} · {{ $v->usuario->name ?? 'Sistema' }}</span>
                                </td>
                                <td class="py-3 px-4 text-xs">{{ $v->cliente->nombre ?? 'Mostrador' }}</td>
                                <td class="py-3 px-4 text-xs">
                                    @if($v->tipo_pago === 'credito')
                                        <span class="insignia-naranja">Fiado</span>
                                    @else
                                        <span class="insignia-verde">Contado{{ $v->moneda_pago === 'VES' ? ' en Bs.' : '' }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-xs">
                                    @if($primero)
                                        {{ $primero->producto->nombre ?? 'Producto eliminado' }} (x{{ \App\Support\Cantidad::formato($primero->cantidad) }})
                                        @if($otros > 0)
                                            <span class="block text-stone-500 dark:text-stone-400">y {{ $otros }} {{ $otros === 1 ? 'producto' : 'productos' }} más</span>
                                        @endif
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right whitespace-nowrap text-emerald-800 dark:text-emerald-400">
                                    <x-precio :usd="$v->total" :ves="$v->total_ves" class="items-end" />
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if($v->editada)
                                        <span class="insignia-ambar">Editada</span>
                                    @else
                                        <span class="insignia-gris">Normal</span>
                                    @endif
                                </td>
                                @if($gestiona)
                                    <td class="py-3 px-4 cursor-default" data-acciones>
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

    {{-- Ventana flotante con el detalle de la venta; el contenido llega por fetch desde ventas.show --}}
    <div id="modal-venta" class="hidden fixed inset-0 z-[190] flex items-center justify-center p-4 no-print" role="dialog" aria-modal="true" aria-labelledby="modal-venta-titulo">
        <div class="absolute inset-0 bg-stone-900/50 dark:bg-black/60" data-cerrar-venta></div>
        <div class="tarjeta relative shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between gap-3 mb-4">
                <h2 id="modal-venta-titulo" class="titulo-tarjeta"></h2>
                <button type="button" data-cerrar-venta class="btn-icono-gris" title="Cerrar" aria-label="Cerrar"><x-icon name="x" class="w-4 h-4" /></button>
            </div>
            <div id="modal-venta-contenido"></div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const modal = document.getElementById('modal-venta');
            const contenido = document.getElementById('modal-venta-contenido');
            let filaOrigen = null;

            function cerrar() {
                modal.classList.add('hidden');
                contenido.innerHTML = '';
                if (filaOrigen) filaOrigen.querySelector('a').focus();
            }

            // El listener va en la tabla (antes que el del layout en document) para que la barra de carga no arranque
            document.getElementById('tabla-ventas').addEventListener('click', async function (e) {
                const desactivado = e.target.closest('[data-motivo]');
                if (desactivado) {
                    mostrarToast(desactivado.dataset.motivo, 'error');
                    return;
                }

                const fila = e.target.closest('tr[data-url]');
                // Las acciones de la fila (editar, eliminar) hacen lo suyo; Ctrl/Cmd + clic en el N° abre otra pestaña
                if (!fila || e.target.closest('[data-acciones]') || e.metaKey || e.ctrlKey || e.shiftKey) return;
                e.preventDefault();
                filaOrigen = fila;

                document.getElementById('modal-venta-titulo').textContent = fila.dataset.titulo;
                contenido.innerHTML = '<p class="text-sm text-stone-500 dark:text-stone-400">Cargando…</p>';
                modal.classList.remove('hidden');
                modal.querySelector('[data-cerrar-venta].btn-icono-gris').focus();

                try {
                    const respuesta = await fetch(fila.dataset.url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    if (!respuesta.ok) throw new Error();
                    contenido.innerHTML = await respuesta.text();
                } catch (error) {
                    // Si falla la carga, se abre la página del detalle
                    window.location.href = fila.dataset.url;
                }
            });

            modal.querySelectorAll('[data-cerrar-venta]').forEach(function (el) { el.addEventListener('click', cerrar); });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && !modal.classList.contains('hidden') && document.getElementById('modal-confirmacion').classList.contains('hidden')) cerrar();
            });
        })();
    </script>
@endpush
