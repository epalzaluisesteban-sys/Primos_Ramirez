@extends('layouts.app')

@section('title', 'Lo que debemos')

@section('content')
    <div class="pagina">

        <x-encabezado titulo="Lo que debemos" subtitulo="Compras a crédito que falta pagar a cada proveedor. Primero los que vencen antes.">
            <x-acciones-exportar :pdf="route('proveedores.deudas.pdf')" />
        </x-encabezado>

        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
            <div class="tarjeta p-4">
                <p class="cifra-etiqueta">Total por pagar</p>
                <x-precio :usd="$totalDeuda" class="cifra-valor {{ $totalDeuda > 0 ? 'text-orange-700 dark:text-orange-400' : '' }}" />
            </div>
            <div class="tarjeta p-4">
                <p class="cifra-etiqueta">Ya vencido</p>
                <x-precio :usd="$totalVencido" class="cifra-valor {{ $totalVencido > 0 ? 'text-rose-600 dark:text-rose-400' : '' }}" />
            </div>
            <div class="tarjeta p-4">
                <p class="cifra-etiqueta">Proveedores con deuda</p>
                <p class="cifra-valor">{{ $deudas->count() }}</p>
            </div>
        </div>

        <div class="tarjeta p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Proveedor</th>
                            <th class="text-right">Comprado a crédito</th>
                            <th class="text-right">Pagado</th>
                            <th class="text-right">Le debemos</th>
                            <th class="text-center">Próximo vencimiento</th>
                            <th class="text-right" aria-label="Acciones"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($deudas as $fila)
                            <tr>
                                <td>
                                    <a href="{{ route('proveedores.show', $fila['proveedor']) }}" class="font-bold text-stone-900 dark:text-stone-100 hover:text-emerald-700 dark:hover:text-emerald-400">{{ $fila['proveedor']->nombre }}</a>
                                    @if($fila['proveedor']->telefono)
                                        <p class="text-xs text-stone-500 dark:text-stone-400">{{ \App\Support\Contacto::telefono($fila['proveedor']->telefono) }}</p>
                                    @endif
                                </td>
                                <td class="text-right whitespace-nowrap">@usd($fila['comprado'])</td>
                                <td class="text-right whitespace-nowrap">@usd($fila['pagado'])</td>
                                <td class="text-right whitespace-nowrap"><x-precio :usd="$fila['saldo']" size="small" class="items-end font-bold text-orange-700 dark:text-orange-400" /></td>
                                <td class="text-center whitespace-nowrap">
                                    @if($fila['vence_el'])
                                        <span class="{{ $fila['vencido'] ? 'insignia-roja' : 'insignia-ambar' }}">
                                            {{ $fila['vencido'] ? 'Vencido' : 'Vence' }} {{ $fila['vence_el']->format('d/m/Y') }}
                                        </span>
                                    @else
                                        <span class="text-xs text-stone-400 dark:text-stone-500">Sin plazo</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('proveedores.show', $fila['proveedor']) }}" class="btn-secundario btn-sm whitespace-nowrap">
                                        <x-icon name="banknote" class="w-4 h-4" /> Pagar
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="tabla-vacia">No se le debe nada a ningún proveedor.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection
