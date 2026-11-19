@extends('layouts.app')

@section('title', 'Cuenta de ' . $cliente->nombre)

@section('content')
    <div class="pagina">

        <x-encabezado :titulo="$cliente->nombre" :volver="route('clientes.index')">
            <x-slot:inicio>
                @if($cliente->fotoUrl())
                    <img src="{{ $cliente->fotoUrl() }}" alt="{{ $cliente->nombre }}" class="w-14 h-14 rounded-md object-cover border border-stone-200 dark:border-stone-700 shrink-0">
                @else
                    <span class="w-14 h-14 rounded-md bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 font-bold text-xl flex items-center justify-center shrink-0">
                        {{ strtoupper(substr($cliente->nombre, 0, 1)) }}
                    </span>
                @endif
            </x-slot:inicio>
            <x-slot:detalle>
                <p class="subtitulo-pagina">
                    {{ $cliente->telefono ? \App\Support\Contacto::telefono($cliente->telefono) : 'Sin teléfono' }}
                    @if($cliente->rif) · RIF: {{ $cliente->rif }} @endif
                    @if($cliente->direccion) · {{ $cliente->direccion }} @endif
                </p>
            </x-slot:detalle>
        </x-encabezado>

        <!-- KPIs -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-4 sm:mb-6">
            <div class="tarjeta p-4">
                <p class="cifra-etiqueta">Saldo pendiente actual</p>
                <x-precio :usd="$saldo" class="cifra-valor {{ $saldo > 0 ? 'text-orange-700 dark:text-orange-400' : 'text-emerald-700 dark:text-emerald-400' }}" />
            </div>
            <div class="tarjeta p-4">
                <p class="cifra-etiqueta">Crédito disponible</p>
                @if($disponible === null)
                    <p class="cifra-valor">Sin límite</p>
                    <p class="text-xs text-stone-500 dark:text-stone-400 mt-1">Ponle un límite de crédito al editarlo si quieres controlar cuánto se le fía.</p>
                @else
                    <x-precio :usd="$disponible" class="cifra-valor {{ $disponible > 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-700 dark:text-rose-400' }}" />
                    <p class="text-xs text-stone-500 dark:text-stone-400 mt-1">
                        Límite de crédito: @usd($cliente->limite_credito).
                        @if($disponible <= 0) No se le puede fiar más hasta que abone. @endif
                    </p>
                @endif
            </div>
            <div class="tarjeta p-4">
                <p class="cifra-etiqueta">Total comprado (histórico)</p>
                <x-precio :usd="$totalComprado" class="cifra-valor" />
            </div>
            <div class="tarjeta p-4">
                <p class="cifra-etiqueta">Última compra</p>
                <p class="cifra-valor">{{ $ultimaCompra ? $ultimaCompra->format('d/m/Y') : 'Sin compras' }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- Registrar abono -->
            <div class="tarjeta h-fit">
                <h3 class="titulo-tarjeta mb-4">Registrar abono
                </h3>

                @if($saldo <= 0)
                    <p class="text-sm text-stone-500 dark:text-stone-400 bg-stone-50 dark:bg-stone-900/60 border border-stone-200 dark:border-stone-700 rounded-md p-4 text-center">
                        Este cliente no tiene saldo pendiente.
                    </p>
                @elseif($estadoCaja === 'pendiente')
                    <div class="aviso-ambar flex-col">
                        <p class="flex items-start gap-2">
                            <x-icon name="alert-triangle" class="w-4 h-4 shrink-0 mt-0.5" />
                            <span>{{ \App\Models\CierreCaja::avisoCajaPendiente() }}</span>
                        </p>
                        <a href="{{ route('caja.index') }}" class="btn-primario btn-sm w-full mt-1">
                            <x-icon name="lock" class="w-4 h-4" /> Ir a cerrar la caja
                        </a>
                    </div>
                @elseif($estadoCaja === 'sin_abrir')
                    <div class="aviso-ambar flex-col">
                        <p class="flex items-start gap-2">
                            <x-icon name="alert-triangle" class="w-4 h-4 shrink-0 mt-0.5" />
                            <span>Para recibir un abono, primero abre la caja del día. Así el pago entra en el cuadre.</span>
                        </p>
                        <a href="{{ route('caja.index') }}" class="btn-primario btn-sm w-full mt-1">
                            <x-icon name="key" class="w-4 h-4" /> Ir a abrir la caja
                        </a>
                    </div>
                @elseif($estadoCaja === 'cerrada')
                    <p class="aviso-ambar">
                        <x-icon name="lock" class="w-4 h-4 shrink-0 mt-0.5" />
                        <span>La caja de hoy ya se cerró. Para recibir el abono, un encargado puede reabrirla en <a href="{{ route('caja.index') }}" class="font-semibold underline">Caja</a> (botón "Reabrir caja").</span>
                    </p>
                @else
                    <form action="{{ route('clientes.abonos.store', $cliente->id) }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="etiqueta">El cliente paga en</label>
                            <div class="flex gap-2 mb-3">
                                <label class="flex-1 flex items-center justify-center gap-2 border-2 border-stone-200 dark:border-stone-700 has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50 dark:has-[:checked]:bg-emerald-900/20 rounded-md py-2 cursor-pointer text-sm font-semibold text-stone-700 dark:text-stone-300">
                                    <input type="radio" name="moneda" value="USD" checked class="accent-emerald-600"> Dólares ($)
                                </label>
                                <label class="flex-1 flex items-center justify-center gap-2 border-2 border-stone-200 dark:border-stone-700 has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50 dark:has-[:checked]:bg-emerald-900/20 rounded-md py-2 cursor-pointer text-sm font-semibold text-stone-700 dark:text-stone-300">
                                    <input type="radio" name="moneda" value="VES" @disabled(\App\Support\Moneda::tasa() <= 0) class="accent-emerald-600"> Bolívares (Bs.)
                                </label>
                            </div>
                            <label class="etiqueta" for="abono-monto">Monto abonado</label>
                            <input type="number" id="abono-monto" step="0.01" min="0.01" max="9999999999" inputmode="decimal" name="monto" value="{{ old('monto') }}" required autofocus class="campo @error('monto') border-rose-500 @enderror">
                            <p class="text-[11px] text-stone-400 dark:text-stone-500 mt-1">Máximo: @usd($saldo) / @bs($saldo) (si paga en Bs. se convierte a la tasa BCV de hoy)</p>
                            @error('monto') <p class="error-campo">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="etiqueta" for="abono-notas">Notas (Opcional)</label>
                            <textarea id="abono-notas" name="notas" rows="2" maxlength="500" class="campo @error('notas') border-rose-500 @enderror">{{ old('notas') }}</textarea>
                            @error('notas') <p class="error-campo">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" class="btn-primario w-full">
                            <x-icon name="check" class="w-4 h-4" /> Registrar abono
                        </button>
                    </form>
                @endif
            </div>

            <!-- Historial -->
            <div class="lg:col-span-2 space-y-6">

                <div class="tarjeta">
                    <h3 class="titulo-tarjeta mb-4">Ventas a crédito (fiado)</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-stone-600 dark:text-stone-300">
                            <thead class="text-stone-500 dark:text-stone-400 uppercase text-xs border-b border-stone-200 dark:border-stone-700">
                                <tr>
                                    <th class="py-2 px-3 font-semibold">Fecha</th>
                                    <th class="py-2 px-3 font-semibold">Productos</th>
                                    <th class="py-2 px-3 font-semibold text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100 dark:divide-stone-700">
                                @forelse($cliente->ventas as $venta)
                                    <tr data-ficha="{{ route('ventas.show', $venta) }}" data-titulo="Venta #{{ $venta->id }}" class="cursor-pointer hover:bg-stone-50 dark:hover:bg-stone-700/40">
                                        <td class="py-2.5 px-3 text-xs"><a href="{{ route('ventas.show', $venta) }}" class="hover:underline" aria-label="Ver detalle de la venta #{{ $venta->id }}">{{ $venta->fecha->format('d/m/Y h:i A') }}</a></td>
                                        <td class="py-2.5 px-3 text-xs">
                                            {{ $venta->detalles->map(fn($d) => $d->producto->nombre . ' x' . \App\Support\Cantidad::formato($d->cantidad))->join(', ') }}
                                        </td>
                                        <td class="py-2.5 px-3 text-right font-bold text-orange-700 dark:text-orange-400">${{ number_format($venta->total, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="py-6 text-center text-stone-400 dark:text-stone-500">Sin ventas a crédito registradas.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tarjeta">
                    <h3 class="titulo-tarjeta mb-4">Historial de abonos</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-stone-600 dark:text-stone-300">
                            <thead class="text-stone-500 dark:text-stone-400 uppercase text-xs border-b border-stone-200 dark:border-stone-700">
                                <tr>
                                    <th class="py-2 px-3 font-semibold">Fecha</th>
                                    <th class="py-2 px-3 font-semibold">Registrado por</th>
                                    <th class="py-2 px-3 font-semibold text-right">Monto</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100 dark:divide-stone-700">
                                @forelse($cliente->abonos as $abono)
                                    <tr>
                                        <td class="py-2.5 px-3 text-xs">{{ $abono->fecha->format('d/m/Y H:i') }}</td>
                                        <td class="py-2.5 px-3 text-xs">{{ $abono->usuario->name ?? 'Sistema' }}</td>
                                        <td class="py-2.5 px-3 text-right font-bold text-emerald-700 dark:text-emerald-400">@usd($abono->monto)
                                            @if($abono->moneda === 'VES')<span class="block text-[10px] font-medium text-stone-500 dark:text-stone-400">Pagó Bs. {{ number_format($abono->monto_moneda, 2, ',', '.') }}</span>@endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="py-6 text-center text-stone-400 dark:text-stone-500">Sin abonos registrados.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

    </div>
@endsection
