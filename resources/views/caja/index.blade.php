@extends('layouts.app')

@section('title', 'Caja del día')

@section('content')
    @php
        $campo = 'campo text-lg font-bold';
        $etiqueta = 'etiqueta';
        $claseDiferencia = fn ($d) => $d < 0 ? 'text-rose-600 dark:text-rose-400' : ($d > 0 ? 'text-blue-600 dark:text-blue-400' : 'text-emerald-600 dark:text-emerald-400');
    @endphp
    <div class="pagina">

        <x-encabezado titulo="Caja del día" subtitulo="Apertura, ventas y cierre de la jornada, en dólares y bolívares.">
            <x-acciones-exportar :excel="route('caja.excel')" :pdf="route('caja.pdf')" />
        </x-encabezado>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- Panel de Control del Día -->
            <div class="tarjeta h-fit">
                <h3 class="titulo-tarjeta mb-4">Estado de caja — {{ date('d/m/Y') }}</h3>

                @if(!$cajaHoy)
                    <div class="aviso-ambar mb-4 text-xs">
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
                        <button type="submit" class="btn-primario w-full">
                            <x-icon name="key" class="w-4 h-4" /> Abrir caja del día
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
                            <label class="{{ $etiqueta }}">Notas / observaciones</label>
                            <textarea name="observaciones" rows="2" placeholder="Ej. Cuadre perfecto / Billete deteriorado..." class="campo text-xs"></textarea>
                        </div>

                        <button type="submit" class="btn-peligro w-full">
                            <x-icon name="lock" class="w-4 h-4" /> Realizar arqueo y cerrar caja
                        </button>
                    </form>

                @else
                    <div class="bg-stone-50 dark:bg-stone-900/60 border border-stone-300 dark:border-stone-700 p-5 rounded-md text-center">
                        <span class="w-10 h-10 rounded-full bg-stone-200 dark:bg-stone-700 flex items-center justify-center mx-auto mb-2">
                            <x-icon name="lock" class="w-4 h-4 text-stone-500 dark:text-stone-400" />
                        </span>
                        <h4 class="font-display font-bold text-stone-800 dark:text-stone-100">Caja cerrada</h4>
                        <p class="text-xs text-stone-500 dark:text-stone-400 mt-1">La jornada del día de hoy ya ha sido auditada y finalizada.</p>
                        <table class="mt-4 w-full text-xs text-left bg-white dark:bg-stone-800 rounded-md border border-stone-200 dark:border-stone-700">
                            <thead class="text-stone-400 uppercase text-[11px]"><tr><th class="p-2"></th><th class="p-2 text-right">Dólares</th><th class="p-2 text-right">Bolívares</th></tr></thead>
                            <tbody class="divide-y divide-stone-100 dark:divide-stone-700">
                                <tr><td class="p-2 font-semibold">Ingresos</td><td class="p-2 text-right">@usd($cajaHoy->monto_ventas_sistema)</td><td class="p-2 text-right">@ves($cajaHoy->monto_ventas_sistema_ves)</td></tr>
                                <tr><td class="p-2 font-semibold">Efectivo contado</td><td class="p-2 text-right">@usd($cajaHoy->monto_efectivo_real)</td><td class="p-2 text-right">@ves($cajaHoy->monto_efectivo_real_ves)</td></tr>
                                <tr><td class="p-2 font-semibold">Diferencia</td>
                                    <td class="p-2 text-right font-bold {{ $claseDiferencia($cajaHoy->diferencia) }}">@usd($cajaHoy->diferencia)</td>
                                    <td class="p-2 text-right font-bold {{ $claseDiferencia($cajaHoy->diferencia_ves) }}">@ves($cajaHoy->diferencia_ves)</td></tr>
                            </tbody>
                        </table>

                        @if($cajaHoy->observaciones)
                            <p class="mt-3 text-[11px] text-left text-stone-500 dark:text-stone-400 whitespace-pre-line">{{ $cajaHoy->observaciones }}</p>
                        @endif

                        @if(auth()->user()->esEncargadoOSuperior())
                            <form action="{{ route('caja.reabrir', $cajaHoy->id) }}" method="POST" class="mt-4"
                                  data-confirm="¿Reabrir la caja de hoy? Podrás seguir registrando ventas y abonos. El cierre anterior queda anotado y tendrás que volver a contar el efectivo al cerrarla.">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn-secundario w-full">
                                    <x-icon name="key" class="w-4 h-4" /> Reabrir caja
                                </button>
                            </form>
                            <p class="ayuda-campo">Úsalo si se cerró por error o falta registrar algo de hoy.</p>
                        @else
                            <p class="ayuda-campo mt-3">¿Se cerró por error? Pide a un encargado que la reabra.</p>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Historial de Arqueos -->
            <div class="tarjeta lg:col-span-2">
                <h3 class="titulo-tarjeta mb-4">Historial reciente de cierres</h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-stone-600 dark:text-stone-300">
                        <thead class="text-stone-500 dark:text-stone-400 uppercase text-xs border-b border-stone-200 dark:border-stone-700">
                            <tr>
                                <th class="py-3 px-4 font-semibold">Fecha</th>
                                <th class="py-3 px-4 font-semibold text-right">Apertura</th>
                                <th class="py-3 px-4 font-semibold text-right">Ingresos</th>
                                <th class="py-3 px-4 font-semibold text-right">Efectivo real</th>
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
                                            <span class="insignia-ambar">Abierta</span>
                                        @else
                                            <span class="bg-stone-100 text-stone-600 dark:bg-stone-700 dark:text-stone-200 font-bold text-xs px-2.5 py-1 rounded">Cerrada</span>
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
