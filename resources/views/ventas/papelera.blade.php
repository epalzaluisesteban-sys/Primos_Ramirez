@extends('layouts.app')

@section('title', 'Papelera de ventas')

@section('content')
    <div class="pagina">

        <x-encabezado titulo="Papelera de ventas" subtitulo="Ventas eliminadas. No cuentan en la caja, los reportes, el kardex ni el saldo de los clientes." :volver="route('ventas.index')" />

        <div class="aviso-azul mb-6">
            <x-icon name="alert-triangle" class="w-4 h-4 mt-0.5 shrink-0" />
            <p>Una venta de hoy se puede restaurar mientras la caja de hoy siga abierta y haya mercancía suficiente. Las de días anteriores se quedan aquí como registro, porque su caja ya se cuadró.</p>
        </div>

        <div class="tarjeta p-0 overflow-hidden">
            <div class="flex items-center gap-2 px-4 py-3 border-b border-stone-200 dark:border-stone-700">
                <h2 class="titulo-tarjeta">Ventas eliminadas</h2>
                <span class="insignia-gris">{{ $ventas->count() }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th class="py-3 px-4">N°</th>
                            <th class="py-3 px-4">Fecha de la venta</th>
                            <th class="py-3 px-4">Cliente</th>
                            <th class="py-3 px-4 text-right">Total</th>
                            <th class="py-3 px-4">Eliminada</th>
                            <th class="py-3 px-4">Motivo</th>
                            <th class="py-3 px-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 dark:divide-stone-700">
                        @forelse($ventas as $v)
                            <tr class="hover:bg-stone-50 dark:hover:bg-stone-700/40">
                                <td class="py-3 px-4 font-bold text-stone-900 dark:text-stone-100">#{{ $v->id }}</td>
                                <td class="py-3 px-4 text-xs whitespace-nowrap">
                                    {{ $v->fecha->format('d/m/Y h:i A') }}
                                    <span class="block text-stone-500 dark:text-stone-400">Atendió {{ $v->usuario->name ?? 'Sistema' }}</span>
                                </td>
                                <td class="py-3 px-4 text-xs">
                                    {{ $v->cliente->nombre ?? 'Mostrador' }}
                                    @if($v->tipo_pago === 'credito') <span class="insignia-naranja">Fiado</span> @endif
                                </td>
                                <td class="py-3 px-4 text-right whitespace-nowrap"><x-precio :usd="$v->total" :ves="$v->total_ves" class="items-end" /></td>
                                <td class="py-3 px-4 text-xs whitespace-nowrap">
                                    {{ optional($v->anulada_at)->format('d/m/Y h:i A') ?? '—' }}
                                    <span class="block text-stone-500 dark:text-stone-400">{{ $v->eliminadaPor->name ?? '' }}</span>
                                </td>
                                <td class="py-3 px-4 text-xs">{{ $v->motivo_anulacion ?: '—' }}</td>
                                <td class="py-3 px-4">
                                    <div class="flex justify-end items-center gap-2">
                                        <a href="{{ route('ventas.show', $v) }}" class="btn-icono-gris" title="Ver detalle" aria-label="Ver detalle de la venta #{{ $v->id }}">
                                            <x-icon name="eye" class="w-4 h-4" />
                                        </a>
                                        @if($v->puedeRestaurarse())
                                            <form action="{{ route('ventas.restaurar', $v) }}" method="POST"
                                                  data-confirm="¿Restaurar la venta #{{ $v->id }}? Se vuelve a descontar la mercancía del inventario y cuenta otra vez en la caja de hoy.">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" class="btn-primario btn-sm"><x-icon name="repetir" class="w-3.5 h-3.5" /> Restaurar</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="tabla-vacia">La papelera está vacía.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
