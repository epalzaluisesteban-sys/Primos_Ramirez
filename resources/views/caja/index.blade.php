@extends('layouts.app')

@section('title', 'Caja del día')

@section('content')
    @php
        $claseDiferencia = fn ($d) => $d < 0 ? 'text-rose-600 dark:text-rose-400' : ($d > 0 ? 'text-blue-600 dark:text-blue-400' : 'text-emerald-600 dark:text-emerald-400');
        $puedeAnotarGastos = $caja && !$cajaPendiente && $caja->estado === 'ABIERTA' && $caja->esDeHoy();
        $gastosUsd = $gastos->where('moneda', 'USD')->sum('monto_moneda');
        $gastosVes = $gastos->where('moneda', 'VES')->sum('monto_moneda');
    @endphp
    <div class="pagina">

        <x-encabezado titulo="Caja del día" subtitulo="Apertura y cierre de la jornada, en dólares y bolívares. Los gastos se anotan en Gastos y se restan aquí.">
            <x-acciones-exportar :excel="route('caja.excel')" :pdf="route('caja.pdf')" />
        </x-encabezado>

        @if($cajaPendiente)
            <div class="aviso-rojo mb-6">
                <x-icon name="alert-triangle" class="w-4 h-4 mt-0.5 shrink-0" />
                <span>
                    La caja del <strong>{{ $cajaPendiente->fechaCorta() }}</strong> quedó abierta. Cuenta el efectivo de ese día y ciérrala aquí.
                    Hasta entonces no se puede abrir la caja de hoy, vender, cobrar abonos ni anotar gastos.
                </span>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <div class="tarjeta h-fit">
                <h3 class="titulo-tarjeta mb-4">
                    {{ $cajaPendiente ? 'Caja pendiente del ' . $cajaPendiente->fechaCorta() : 'Estado de caja — ' . now()->format('d/m/Y') }}
                </h3>

                @if(!$caja)
                    <div class="aviso-ambar mb-4 text-xs">
                        <x-icon name="alert-triangle" class="w-4 h-4 mt-0.5 shrink-0 text-amber-500" />
                        No se ha aperturado la caja para la jornada de hoy.
                    </div>

                    <form action="{{ route('caja.abrir') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="etiqueta" for="monto_apertura">Fondo inicial en dólares ($)</label>
                            <input type="number" step="0.01" min="0" max="99999999" inputmode="decimal" id="monto_apertura" name="monto_apertura"
                                   value="{{ old('monto_apertura', '0.00') }}" required class="campo @error('monto_apertura') border-rose-500 @enderror">
                            @error('monto_apertura') <p class="error-campo">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="etiqueta" for="monto_apertura_ves">Fondo inicial en bolívares (Bs.)</label>
                            <input type="number" step="0.01" min="0" max="9999999999" inputmode="decimal" id="monto_apertura_ves" name="monto_apertura_ves"
                                   value="{{ old('monto_apertura_ves', '0.00') }}" required class="campo @error('monto_apertura_ves') border-rose-500 @enderror">
                            @error('monto_apertura_ves') <p class="error-campo">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" class="btn-primario w-full">
                            <x-icon name="key" class="w-4 h-4" /> Abrir caja del día
                        </button>
                    </form>

                @elseif($caja->estado === 'ABIERTA')
                    <div class="text-sm text-stone-600 dark:text-stone-300 mb-6 border-b border-stone-200 dark:border-stone-700 pb-4">
                        <table class="w-full">
                            <thead class="text-[11px] uppercase text-stone-400 dark:text-stone-500">
                                <tr><th class="text-left font-semibold pb-1.5"></th><th class="text-right font-semibold pb-1.5">Dólares</th><th class="text-right font-semibold pb-1.5">Bolívares</th></tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100 dark:divide-stone-700">
                                <tr><td class="py-1.5">Fondo inicial</td><td class="text-right font-bold text-stone-800 dark:text-stone-100">@usd($caja->monto_apertura)</td><td class="text-right font-bold text-stone-800 dark:text-stone-100">@ves($caja->monto_apertura_ves)</td></tr>
                                <tr><td class="py-1.5">Ventas de contado</td><td class="text-right font-bold text-emerald-800 dark:text-emerald-400">@usd($resumen['contado_usd'])</td><td class="text-right font-bold text-emerald-800 dark:text-emerald-400">@ves($resumen['contado_ves'])</td></tr>
                                <tr><td class="py-1.5">Abonos cobrados</td><td class="text-right font-bold text-emerald-800 dark:text-emerald-400">@usd($resumen['abonos_usd'])</td><td class="text-right font-bold text-emerald-800 dark:text-emerald-400">@ves($resumen['abonos_ves'])</td></tr>
                                <tr><td class="py-1.5">Gastos</td><td class="text-right font-bold text-rose-700 dark:text-rose-400">− @usd($resumen['gastos_usd'])</td><td class="text-right font-bold text-rose-700 dark:text-rose-400">− @ves($resumen['gastos_ves'])</td></tr>
                                @if($resumen['pagos_usd'] > 0 || $resumen['pagos_ves'] > 0)
                                    <tr><td class="py-1.5">Pagos a proveedores</td><td class="text-right font-bold text-rose-700 dark:text-rose-400">− @usd($resumen['pagos_usd'])</td><td class="text-right font-bold text-rose-700 dark:text-rose-400">− @ves($resumen['pagos_ves'])</td></tr>
                                @endif
                                <tr class="bg-stone-50 dark:bg-stone-900/60 font-extrabold text-stone-900 dark:text-stone-100">
                                    <td class="py-2.5 pl-2.5 rounded-l-lg">Esperado en caja</td><td class="text-right">@usd($esperado['usd'])</td><td class="text-right pr-2.5 rounded-r-lg">@ves($esperado['ves'])</td>
                                </tr>
                            </tbody>
                        </table>
                        <p class="text-xs text-stone-500 dark:text-stone-400 mt-3">
                            Ventas a crédito del día: @usd($resumen['credito']) (no ingresan efectivo hasta que el cliente abone).
                        </p>
                    </div>

                    <form action="{{ route('caja.cerrar', $caja->id) }}" method="POST" data-confirm="¿Cerrar la caja del {{ $caja->fechaCorta() }}? Después no se podrán registrar ventas, abonos ni gastos de ese día." class="space-y-4">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="etiqueta" for="monto_efectivo_real">Efectivo contado en dólares ($)</label>
                            <input type="number" step="0.01" min="0" max="99999999" inputmode="decimal" id="monto_efectivo_real" name="monto_efectivo_real"
                                   value="{{ old('monto_efectivo_real') }}" required placeholder="0.00" class="campo @error('monto_efectivo_real') border-rose-500 @enderror">
                            @error('monto_efectivo_real') <p class="error-campo">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="etiqueta" for="monto_efectivo_real_ves">Efectivo contado en bolívares (Bs.)</label>
                            <input type="number" step="0.01" min="0" max="9999999999" inputmode="decimal" id="monto_efectivo_real_ves" name="monto_efectivo_real_ves"
                                   value="{{ old('monto_efectivo_real_ves') }}" required placeholder="0.00" class="campo @error('monto_efectivo_real_ves') border-rose-500 @enderror">
                            @error('monto_efectivo_real_ves') <p class="error-campo">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="etiqueta" for="observaciones">Notas (Opcional)</label>
                            <textarea id="observaciones" name="observaciones" rows="2" maxlength="500" placeholder="Ej. Billete deteriorado" class="campo @error('observaciones') border-rose-500 @enderror">{{ old('observaciones') }}</textarea>
                            @error('observaciones') <p class="error-campo">{{ $message }}</p> @enderror
                        </div>

                        <button type="submit" class="btn-peligro w-full">
                            <x-icon name="lock" class="w-4 h-4" /> Contar efectivo y cerrar caja
                        </button>
                        <p class="ayuda-campo">Al cerrar podrás descargar el reporte del día en PDF y el ticket del cierre.</p>
                    </form>

                @else
                    @php
                        $esperadoUsd = $caja->monto_apertura + $caja->monto_ventas_sistema - $caja->monto_gastos;
                        $esperadoVes = $caja->monto_apertura_ves + $caja->monto_ventas_sistema_ves - $caja->monto_gastos_ves;
                    @endphp
                    <div class="caja-suave p-4">
                        <p class="font-display font-bold text-stone-800 dark:text-stone-100 flex items-center gap-2">
                            <x-icon name="lock" class="w-4 h-4 text-stone-500 dark:text-stone-400" /> Caja cerrada
                        </p>
                        <p class="text-xs text-stone-500 dark:text-stone-400 mt-1">
                            {{ $caja->cerradaPor ? 'Cerrada por ' . $caja->cerradaPor->name : 'Cerrada' }}{{ $caja->cerrada_at ? ' a las ' . $caja->cerrada_at->format('h:i A') : '' }}.
                        </p>
                        <table class="mt-4 w-full text-xs text-left">
                            <thead class="text-stone-400 dark:text-stone-500 uppercase text-[11px]"><tr><th class="py-1.5"></th><th class="py-1.5 text-right">Dólares</th><th class="py-1.5 text-right">Bolívares</th></tr></thead>
                            <tbody class="divide-y divide-stone-200 dark:divide-stone-700 text-stone-700 dark:text-stone-200">
                                <tr><td class="py-1.5 font-semibold">Fondo inicial</td><td class="py-1.5 text-right">@usd($caja->monto_apertura)</td><td class="py-1.5 text-right">@ves($caja->monto_apertura_ves)</td></tr>
                                <tr><td class="py-1.5 font-semibold">Ingresos</td><td class="py-1.5 text-right">@usd($caja->monto_ventas_sistema)</td><td class="py-1.5 text-right">@ves($caja->monto_ventas_sistema_ves)</td></tr>
                                <tr><td class="py-1.5 font-semibold">Gastos y pagos</td><td class="py-1.5 text-right">− @usd($caja->monto_gastos)</td><td class="py-1.5 text-right">− @ves($caja->monto_gastos_ves)</td></tr>
                                <tr><td class="py-1.5 font-semibold">Esperado</td><td class="py-1.5 text-right">@usd($esperadoUsd)</td><td class="py-1.5 text-right">@ves($esperadoVes)</td></tr>
                                <tr><td class="py-1.5 font-semibold">Efectivo contado</td><td class="py-1.5 text-right">@usd($caja->monto_efectivo_real)</td><td class="py-1.5 text-right">@ves($caja->monto_efectivo_real_ves)</td></tr>
                                <tr><td class="py-1.5 font-semibold">Diferencia</td>
                                    <td class="py-1.5 text-right font-bold {{ $claseDiferencia($caja->diferencia) }}">@usd($caja->diferencia)</td>
                                    <td class="py-1.5 text-right font-bold {{ $claseDiferencia($caja->diferencia_ves) }}">@ves($caja->diferencia_ves)</td></tr>
                            </tbody>
                        </table>

                        @if($caja->observaciones)
                            <p class="mt-3 text-[11px] text-stone-500 dark:text-stone-400 whitespace-pre-line">{{ $caja->observaciones }}</p>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-2 mt-4">
                        <a href="{{ route('caja.reporte', $caja) }}" class="btn-primario">
                            <x-icon name="download" class="w-4 h-4" /> Reporte PDF
                        </a>
                        <a href="{{ route('caja.ticket', $caja) }}" target="_blank" rel="noopener" class="btn-secundario">
                            <x-icon name="printer" class="w-4 h-4" /> Ticket
                        </a>
                    </div>

                    @if(auth()->user()->esEncargadoOSuperior())
                        <form action="{{ route('caja.reabrir', $caja->id) }}" method="POST" class="mt-4"
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
                @endif
            </div>

            <div class="lg:col-span-2 space-y-8">

                @if($caja)
                    {{-- Los gastos tienen su propia pantalla (registro, filtros y reportes); aquí solo el resumen del día --}}
                    <div class="tarjeta">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <h3 class="titulo-tarjeta">Gastos del día</h3>
                                <p class="ayuda-campo">
                                    {{ $gastos->count() === 0 ? 'No hay gastos anotados en este día.' : $gastos->count() . ($gastos->count() === 1 ? ' gasto anotado' : ' gastos anotados') . '. Se restan del efectivo esperado.' }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="font-display text-xl font-bold text-rose-700 dark:text-rose-400">− @usd($gastosUsd)</p>
                                <p class="text-xs font-semibold text-rose-700 dark:text-rose-400">− @ves($gastosVes)</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2 mt-4">
                            @if($puedeAnotarGastos)
                                <a href="{{ route('gastos.index', ['anotar' => 1]) }}" class="btn-primario btn-sm"><x-icon name="plus" class="w-4 h-4" /> Anotar gasto</a>
                            @endif
                            <a href="{{ route('gastos.index', ['fecha_inicio' => \Illuminate\Support\Carbon::parse($caja->fecha)->toDateString(), 'fecha_fin' => \Illuminate\Support\Carbon::parse($caja->fecha)->toDateString()]) }}" class="btn-secundario btn-sm">Ver los gastos del día</a>
                        </div>
                    </div>
                @endif

                <div class="tarjeta">
                    <h3 class="titulo-tarjeta mb-4">Historial reciente de cierres</h3>

                    <div class="overflow-x-auto">
                        <table class="tabla">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th class="hidden md:table-cell text-right">Apertura</th>
                                    <th class="text-right">Ingresos</th>
                                    <th class="hidden md:table-cell text-right">Gastos y pagos</th>
                                    <th class="hidden lg:table-cell text-right">Efectivo real</th>
                                    <th class="text-right">Diferencia</th>
                                    <th class="hidden sm:table-cell text-center">Estado</th>
                                    <th class="text-right"><span class="sr-only">Reporte</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($historialCierres as $cierre)
                                    <tr data-ficha="{{ route('caja.show', $cierre) }}" data-titulo="Caja del {{ $cierre->fechaCorta() }}" class="cursor-pointer">
                                        <td class="text-xs whitespace-nowrap"><a href="{{ route('caja.show', $cierre) }}" class="font-bold text-stone-800 dark:text-stone-100 hover:underline" aria-label="Ver la caja del {{ $cierre->fechaCorta() }}">{{ $cierre->fechaCorta() }}</a></td>
                                        <td class="hidden md:table-cell text-right"><x-precio :usd="$cierre->monto_apertura" :ves="$cierre->monto_apertura_ves" size="small" class="items-end" /></td>
                                        <td class="text-right"><x-precio :usd="$cierre->monto_ventas_sistema" :ves="$cierre->monto_ventas_sistema_ves" size="small" class="items-end" /></td>
                                        <td class="hidden md:table-cell text-right"><x-precio :usd="$cierre->monto_gastos" :ves="$cierre->monto_gastos_ves" size="small" class="items-end" /></td>
                                        <td class="hidden lg:table-cell text-right"><x-precio :usd="$cierre->monto_efectivo_real ?? 0" :ves="$cierre->monto_efectivo_real_ves ?? 0" size="small" class="items-end" /></td>
                                        <td class="text-right text-xs {{ $claseDiferencia($cierre->diferencia) }}">
                                            @if($cierre->estado === 'CERRADA')
                                                <x-precio :usd="$cierre->diferencia" :ves="$cierre->diferencia_ves ?? 0" size="small" class="items-end" />
                                            @else — @endif
                                        </td>
                                        <td class="hidden sm:table-cell text-center">
                                            @if($cierre->estado === 'ABIERTA')
                                                <span class="insignia-ambar">Abierta</span>
                                            @else
                                                <span class="insignia-gris">Cerrada</span>
                                            @endif
                                        </td>
                                        <td class="text-right cursor-default" data-acciones>
                                            <a href="{{ route('caja.reporte', $cierre) }}" class="btn-icono-gris" title="Reporte del día en PDF" aria-label="Reporte del día en PDF">
                                                <x-icon name="download" class="w-4 h-4" />
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="tabla-vacia">No hay registros de caja previos.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <p class="ayuda-campo mt-3">Toca un día para ver su cuadre completo, el reporte en PDF y el ticket.</p>
                </div>
            </div>

        </div>

    </div>
@endsection
