@extends('layouts.app')

@section('title', 'Cuenta de ' . $cliente->nombre)

@section('content')
    <div class="max-w-5xl mx-auto px-4 py-8">

        <div class="mb-6 flex items-center justify-between">
            <div class="flex items-center gap-4">
                @if($cliente->fotoUrl())
                    <img src="{{ $cliente->fotoUrl() }}" alt="{{ $cliente->nombre }}" class="w-16 h-16 rounded-full object-cover border border-stone-300 dark:border-stone-700">
                @else
                    <span class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 font-bold text-xl flex items-center justify-center border border-stone-300 dark:border-stone-700">
                        {{ strtoupper(substr($cliente->nombre, 0, 1)) }}
                    </span>
                @endif
                <div>
                    <h2 class="font-display text-xl font-bold text-stone-900 dark:text-stone-100">{{ $cliente->nombre }}</h2>
                    <p class="text-sm text-stone-500 dark:text-stone-400 mt-0.5">
                        {{ $cliente->telefono ?: 'Sin teléfono' }}
                        @if($cliente->rif) · RIF: {{ $cliente->rif }} @endif
                    </p>
                    @if($cliente->direccion)
                        <p class="text-xs text-stone-400 dark:text-stone-500 mt-0.5">{{ $cliente->direccion }}</p>
                    @endif
                </div>
            </div>
            <a href="{{ route('clientes.index') }}" class="flex items-center gap-1.5 text-sm font-semibold text-stone-500 dark:text-stone-400 hover:text-stone-800 dark:hover:text-stone-100">
                <x-icon name="arrow-left" class="w-4 h-4" /> Volver a clientes
            </a>
        </div>

        <!-- KPIs -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
            <div class="bg-white dark:bg-stone-800 rounded-2xl shadow-sm border border-stone-200 dark:border-stone-700 p-6 border-t-4 {{ $saldo > 0 ? 'border-orange-500' : 'border-emerald-500' }}">
                <p class="text-xs font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wide">Saldo Pendiente Actual</p>
                <p class="font-display text-2xl font-extrabold {{ $saldo > 0 ? 'text-orange-700 dark:text-orange-400' : 'text-emerald-700 dark:text-emerald-400' }} mt-1">
                    <x-precio :usd="$saldo" />
                </p>
                @if($cliente->limite_credito)
                    <p class="text-xs text-stone-400 dark:text-stone-500 mt-1">Límite de crédito: @usd($cliente->limite_credito)</p>
                @endif
            </div>
            <div class="bg-white dark:bg-stone-800 rounded-2xl shadow-sm border border-stone-200 dark:border-stone-700 p-6 border-t-4 border-blue-500">
                <p class="text-xs font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wide">Total Comprado (Histórico)</p>
                <x-precio :usd="$totalComprado" class="mt-1 font-display text-2xl font-extrabold text-stone-800 dark:text-stone-100" />
            </div>
            <div class="bg-white dark:bg-stone-800 rounded-2xl shadow-sm border border-stone-200 dark:border-stone-700 p-6 border-t-4 border-stone-400">
                <p class="text-xs font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wide">Última Compra</p>
                <p class="font-display text-2xl font-extrabold text-stone-800 dark:text-stone-100 mt-1">{{ $ultimaCompra ? $ultimaCompra->format('d/m/Y') : 'Sin compras' }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- Registrar abono -->
            <div class="bg-white dark:bg-stone-800 rounded-2xl shadow-sm border border-stone-200 dark:border-stone-700 p-6 h-fit">
                <h3 class="font-display text-base font-bold text-stone-800 dark:text-stone-100 mb-4 flex items-center gap-2">
                    <x-icon name="banknote" class="w-4 h-4 text-emerald-600 dark:text-emerald-400" /> Registrar Abono
                </h3>

                @if($saldo <= 0)
                    <p class="text-sm text-stone-500 dark:text-stone-400 bg-stone-50 dark:bg-stone-900/60 border border-stone-200 dark:border-stone-700 rounded-xl p-4 text-center">
                        Este cliente no tiene saldo pendiente.
                    </p>
                @else
                    <form action="{{ route('clientes.abonos.store', $cliente->id) }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold text-stone-600 dark:text-stone-300 uppercase mb-1.5">El cliente paga en</label>
                            <div class="flex gap-2 mb-3">
                                <label class="flex-1 flex items-center justify-center gap-2 border-2 border-stone-200 dark:border-stone-700 has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50 dark:has-[:checked]:bg-emerald-900/20 rounded-xl py-2 cursor-pointer text-sm font-semibold text-stone-700 dark:text-stone-300">
                                    <input type="radio" name="moneda" value="USD" checked class="accent-emerald-600"> Dólares ($)
                                </label>
                                <label class="flex-1 flex items-center justify-center gap-2 border-2 border-stone-200 dark:border-stone-700 has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50 dark:has-[:checked]:bg-emerald-900/20 rounded-xl py-2 cursor-pointer text-sm font-semibold text-stone-700 dark:text-stone-300">
                                    <input type="radio" name="moneda" value="VES" @disabled(\App\Support\Moneda::tasa() <= 0) class="accent-emerald-600"> Bolívares (Bs.)
                                </label>
                            </div>
                            <label class="block text-xs font-semibold text-stone-600 dark:text-stone-300 uppercase mb-1.5">Monto abonado</label>
                            <input type="number" step="0.01" min="0.01" name="monto" required class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-stone-900 dark:border-stone-600 dark:text-stone-100 dark:placeholder-stone-500">
                            <p class="text-[11px] text-stone-400 dark:text-stone-500 mt-1">Máximo: @usd($saldo) / @bs($saldo) (si paga en Bs. se convierte a la tasa BCV de hoy)</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-stone-600 dark:text-stone-300 uppercase mb-1.5">Notas (Opcional)</label>
                            <textarea name="notas" rows="2" class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-stone-900 dark:border-stone-600 dark:text-stone-100 dark:placeholder-stone-500"></textarea>
                        </div>
                        <button type="submit" class="w-full flex items-center justify-center gap-2 bg-emerald-700 hover:bg-emerald-800 text-white font-semibold py-2.5 px-4 rounded-xl shadow-sm transition text-sm">
                            <x-icon name="check" class="w-4 h-4" /> Registrar Abono
                        </button>
                    </form>
                @endif
            </div>

            <!-- Historial -->
            <div class="lg:col-span-2 space-y-6">

                <div class="bg-white dark:bg-stone-800 rounded-2xl shadow-sm border border-stone-200 dark:border-stone-700 p-6">
                    <h3 class="font-display text-base font-bold text-stone-800 dark:text-stone-100 mb-4">Ventas a Crédito (Fiado)</h3>
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
                                    <tr>
                                        <td class="py-2.5 px-3 text-xs">{{ $venta->fecha->format('d/m/Y H:i') }}</td>
                                        <td class="py-2.5 px-3 text-xs">
                                            {{ $venta->detalles->map(fn($d) => $d->producto->nombre . ' x' . $d->cantidad)->join(', ') }}
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

                <div class="bg-white dark:bg-stone-800 rounded-2xl shadow-sm border border-stone-200 dark:border-stone-700 p-6">
                    <h3 class="font-display text-base font-bold text-stone-800 dark:text-stone-100 mb-4">Historial de Abonos</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-stone-600 dark:text-stone-300">
                            <thead class="text-stone-500 dark:text-stone-400 uppercase text-xs border-b border-stone-200 dark:border-stone-700">
                                <tr>
                                    <th class="py-2 px-3 font-semibold">Fecha</th>
                                    <th class="py-2 px-3 font-semibold">Registrado Por</th>
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
