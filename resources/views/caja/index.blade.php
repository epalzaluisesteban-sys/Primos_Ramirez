@extends('layouts.app')

@section('title', 'Arqueo de Caja')

@section('content')
    @php
        $campo = 'w-full rounded-lg border-stone-300 dark:border-stone-600 border p-2.5 text-lg font-bold bg-white dark:bg-stone-900 text-stone-800 dark:text-stone-100 placeholder-stone-400 dark:placeholder-stone-500 focus:ring-emerald-500 focus:border-emerald-500';
        $etiqueta = 'block text-xs font-semibold text-stone-600 dark:text-stone-300 uppercase mb-1.5';
        $claseDiferencia = fn ($d) => $d < 0 ? 'text-rose-600 dark:text-rose-400' : ($d > 0 ? 'text-blue-600 dark:text-blue-400' : 'text-emerald-600 dark:text-emerald-400');
    @endphp
    <div class="max-w-7xl mx-auto px-4 py-8">

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <div>
                <h2 class="font-display text-xl font-bold text-stone-900 dark:text-stone-100">Arqueo de Caja</h2>
                <p class="text-sm text-stone-500 dark:text-stone-400 mt-0.5">Control de apertura, ventas y cierre de la jornada, en dólares y bolívares.</p>
            </div>
            <x-acciones-exportar :excel="route('caja.excel')" :pdf="route('caja.pdf')" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- Panel de Control del Día -->
            <div class="bg-white dark:bg-stone-800 rounded-2xl shadow-sm border border-stone-200 dark:border-stone-700 p-6 border-t-4 border-emerald-600 h-fit">
                <h3 class="font-display text-base font-bold text-stone-800 dark:text-stone-100 mb-4">Estado de Caja — {{ date('d/m/Y') }}</h3>

                @if(!$cajaHoy)
                    <div class="flex items-start gap-2.5 bg-amber-50 border border-amber-200 dark:bg-amber-900/20 dark:border-amber-800 p-3.5 rounded-xl mb-4 text-xs text-amber-800 dark:text-amber-300">
                        <x-icon name="alert-triangle" class="w-4 h-4 mt-0.5 shrink-0 text-amber-500" />
                        No se ha aperturado la caja para la jornada de hoy.
                    </div>

                    <form action="{{ route('caja.abrir') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="{{ $etiqueta }}">Fondo inicial en dólares ($)</label>
                            <input type="number" step="0.01" min="0" name="monto_apertura" value="0.00" required class="{{ $campo }}">
                        </div>
                        <div>
                            <label class="{{ $etiqueta }}">Fondo inicial en bolívares (Bs.)</label>
                            <input type="number" step="0.01" min="0" name="monto_apertura_ves" value="0.00" required class="{{ $campo }}">
                        </div>
                        <button type="submit" class="w-full flex items-center justify-center gap-2 bg-emerald-700 hover:bg-emerald-800 text-white font-semibold py-2.5 px-4 rounded-xl shadow-sm transition text-sm">
                            <x-icon name="key" class="w-4 h-4" /> Abrir Caja del Día
                        </button>
                    </form>

                @elseif($cajaHoy->estado === 'ABIERTA')
                    @php
                        $esperadoUsd = $cajaHoy->monto_apertura + $resumen['ingresos_usd'];
                        $esperadoVes = $cajaHoy->monto_apertura_ves + $resumen['ingresos_ves'];
                    @endphp
                    <div class="text-sm text-stone-600 dark:text-stone-300 mb-6 border-b border-stone-200 dark:border-stone-700 pb-4">
                        <table class="w-full">
                            <thead class="text-[11px] uppercase text-stone-400 dark:text-stone-500">
                                <tr><th class="text-left font-semibold pb-1.5"></th><th class="text-right font-semibold pb-1.5">Dólares</th><th class="text-right font-semibold pb-1.5">Bolívares</th></tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100 dark:divide-stone-700">
                                <tr><td class="py-1.5">Fondo inicial</td><td class="text-right font-bold text-stone-800 dark:text-stone-100">@usd($cajaHoy->monto_apertura)</td><td class="text-right font-bold text-stone-800 dark:text-stone-100">@ves($cajaHoy->monto_apertura_ves)</td></tr>
                                <tr><td class="py-1.5">Ventas de contado</td><td class="text-right font-bold text-emerald-800 dark:text-emerald-400">@usd($resumen['contado_usd'])</td><td class="text-right font-bold text-emerald-800 dark:text-emerald-400">@ves($resumen['contado_ves'])</td></tr>
                                <tr><td class="py-1.5">Abonos cobrados</td><td class="text-right font-bold text-emerald-800 dark:text-emerald-400">@usd($resumen['abonos_usd'])</td><td class="text-right font-bold text-emerald-800 dark:text-emerald-400">@ves($resumen['abonos_ves'])</td></tr>
                                <tr class="bg-stone-50 dark:bg-stone-900/60 font-extrabold text-stone-900 dark:text-stone-100">
                                    <td class="py-2.5 pl-2.5 rounded-l-lg">Esperado en caja</td><td class="text-right">@usd($esperadoUsd)</td><td class="text-right pr-2.5 rounded-r-lg">@ves($esperadoVes)</td>
                                </tr>
                            </tbody>
                        </table>
                        <p class="text-xs text-stone-500 dark:text-stone-400 mt-3">
                            Ventas a crédito de hoy: @usd($resumen['credito']) (no ingresan efectivo hasta que el cliente abone).
                        </p>
                    </div>

                    <form action="{{ route('caja.cerrar', $cajaHoy->id) }}" method="POST" data-confirm="¿Confirma que desea cerrar la caja del día?" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="{{ $etiqueta }}">Efectivo contado en dólares ($)</label>
                            <input type="number" step="0.01" min="0" name="monto_efectivo_real" required placeholder="0.00" class="{{ $campo }}">
                        </div>
                        <div>
                            <label class="{{ $etiqueta }}">Efectivo contado en bolívares (Bs.)</label>
                            <input type="number" step="0.01" min="0" name="monto_efectivo_real_ves" required placeholder="0.00" class="{{ $campo }}">
                        </div>
                        <div>
                            <label class="{{ $etiqueta }}">Notas / Observaciones</label>
                            <textarea name="observaciones" rows="2" placeholder="Ej. Cuadre perfecto / Billete deteriorado..." class="w-full rounded-lg border-stone-300 dark:border-stone-600 border p-2.5 text-xs bg-white dark:bg-stone-900 text-stone-800 dark:text-stone-100 placeholder-stone-400 dark:placeholder-stone-500 focus:ring-emerald-500 focus:border-emerald-500"></textarea>
                        </div>

                        <button type="submit" class="w-full flex items-center justify-center gap-2 bg-rose-600 hover:bg-rose-700 text-white font-semibold py-2.5 px-4 rounded-xl shadow-sm transition text-sm">
                            <x-icon name="lock" class="w-4 h-4" /> Realizar Arqueo y Cerrar Caja
                        </button>
                    </form>

                @else
                    <div class="bg-stone-50 dark:bg-stone-900/60 border border-stone-300 dark:border-stone-700 p-5 rounded-xl text-center">
                        <span class="w-10 h-10 rounded-full bg-stone-200 dark:bg-stone-700 flex items-center justify-center mx-auto mb-2">
                            <x-icon name="lock" class="w-4 h-4 text-stone-500 dark:text-stone-400" />
                        </span>
                        <h4 class="font-display font-bold text-stone-800 dark:text-stone-100">Caja Cerrada</h4>
                        <p class="text-xs text-stone-500 dark:text-stone-400 mt-1">La jornada del día de hoy ya ha sido auditada y finalizada.</p>
                        <table class="mt-4 w-full text-xs text-left bg-white dark:bg-stone-800 rounded-lg border border-stone-200 dark:border-stone-700">
                            <thead class="text-stone-400 uppercase text-[11px]"><tr><th class="p-2"></th><th class="p-2 text-right">Dólares</th><th class="p-2 text-right">Bolívares</th></tr></thead>
                            <tbody class="divide-y divide-stone-100 dark:divide-stone-700">
                                <tr><td class="p-2 font-semibold">Ingresos</td><td class="p-2 text-right">@usd($cajaHoy->monto_ventas_sistema)</td><td class="p-2 text-right">@ves($cajaHoy->monto_ventas_sistema_ves)</td></tr>
                                <tr><td class="p-2 font-semibold">Efectivo contado</td><td class="p-2 text-right">@usd($cajaHoy->monto_efectivo_real)</td><td class="p-2 text-right">@ves($cajaHoy->monto_efectivo_real_ves)</td></tr>
                                <tr><td class="p-2 font-semibold">Diferencia</td>
                                    <td class="p-2 text-right font-bold {{ $claseDiferencia($cajaHoy->diferencia) }}">@usd($cajaHoy->diferencia)</td>
                                    <td class="p-2 text-right font-bold {{ $claseDiferencia($cajaHoy->diferencia_ves) }}">@ves($cajaHoy->diferencia_ves)</td></tr>
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <!-- Historial de Arqueos -->
            <div class="lg:col-span-2 bg-white dark:bg-stone-800 rounded-2xl shadow-sm border border-stone-200 dark:border-stone-700 p-6">
                <h3 class="font-display text-base font-bold text-stone-800 dark:text-stone-100 mb-4">Historial Reciente de Cierres</h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-stone-600 dark:text-stone-300">
                        <thead class="text-stone-500 dark:text-stone-400 uppercase text-xs border-b border-stone-200 dark:border-stone-700">
                            <tr>
                                <th class="py-3 px-4 font-semibold">Fecha</th>
                                <th class="py-3 px-4 font-semibold text-right">Apertura</th>
                                <th class="py-3 px-4 font-semibold text-right">Ingresos</th>
                                <th class="py-3 px-4 font-semibold text-right">Efectivo Real</th>
                                <th class="py-3 px-4 font-semibold text-right">Diferencia</th>
                                <th class="py-3 px-4 font-semibold text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100 dark:divide-stone-700">
                            @forelse($historialCierres as $cierre)
                                <tr class="hover:bg-stone-50 dark:hover:bg-stone-700/40">
                                    <td class="py-3 px-4 text-xs font-bold text-stone-800 dark:text-stone-100">{{ date('d/m/Y', strtotime($cierre->fecha)) }}</td>
                                    <td class="py-3 px-4 text-right"><x-precio :usd="$cierre->monto_apertura" :ves="$cierre->monto_apertura_ves" size="small" class="items-end" /></td>
                                    <td class="py-3 px-4 text-right"><x-precio :usd="$cierre->monto_ventas_sistema" :ves="$cierre->monto_ventas_sistema_ves" size="small" class="items-end" /></td>
                                    <td class="py-3 px-4 text-right"><x-precio :usd="$cierre->monto_efectivo_real ?? 0" :ves="$cierre->monto_efectivo_real_ves ?? 0" size="small" class="items-end" /></td>
                                    <td class="py-3 px-4 text-right text-xs {{ $claseDiferencia($cierre->diferencia) }}">
                                        @if($cierre->estado === 'CERRADA')
                                            <x-precio :usd="$cierre->diferencia" :ves="$cierre->diferencia_ves ?? 0" size="small" class="items-end" />
                                        @else — @endif
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        @if($cierre->estado === 'ABIERTA')
                                            <span class="bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 font-bold text-xs px-2.5 py-1 rounded-full">Abierta</span>
                                        @else
                                            <span class="bg-stone-100 text-stone-600 dark:bg-stone-700 dark:text-stone-200 font-bold text-xs px-2.5 py-1 rounded-full">Cerrada</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-6 text-center text-stone-500 dark:text-stone-400">No hay registros de caja previos.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>
@endsection
