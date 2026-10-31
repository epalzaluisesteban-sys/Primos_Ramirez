@extends('layouts.app')

@section('title', 'Pérdidas de Mercancía')

@section('content')
    <div class="max-w-7xl mx-auto px-4 py-8">

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <div>
                <h2 class="font-display text-xl font-bold text-stone-900 dark:text-stone-100">Pérdidas de Mercancía</h2>
                <p class="text-sm text-stone-500 dark:text-stone-400 mt-0.5">Registra producto dañado, vencido o extraviado y mantén el inventario exacto.</p>
            </div>
            <x-acciones-exportar :excel="route('mermas.excel')" :pdf="route('mermas.pdf')" />
        </div>

        <!-- Resumen del mes -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">
            <div class="bg-white dark:bg-stone-800 rounded-2xl shadow-sm border border-stone-200 dark:border-stone-700 p-6 border-t-4 border-rose-500">
                <p class="text-xs font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wide">Valor Perdido Este Mes (a precio venta)</p>
                <p class="font-display text-2xl font-bold text-rose-600 dark:text-rose-400 mt-1">${{ number_format($valorPerdidoMes, 2) }}</p>
            </div>
            <div class="bg-white dark:bg-stone-800 rounded-2xl shadow-sm border border-stone-200 dark:border-stone-700 p-6 border-t-4 border-amber-500">
                <p class="text-xs font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wide">Unidades Descartadas Este Mes</p>
                <p class="font-display text-2xl font-bold text-stone-800 dark:text-stone-100 mt-1">{{ $cantidadMes }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- Formulario de Registro -->
            <div class="bg-white dark:bg-stone-800 rounded-2xl shadow-sm border border-stone-200 dark:border-stone-700 p-6 h-fit">
                <h3 class="font-display text-base font-bold text-stone-800 dark:text-stone-100 mb-4 flex items-center gap-2">
                    <x-icon name="package-x" class="w-4 h-4 text-rose-600" /> Registrar Pérdida
                </h3>

                <form action="{{ route('mermas.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-stone-600 dark:text-stone-300 uppercase mb-1.5">Producto</label>
                        <select name="producto_id" required class="w-full rounded-lg border-stone-300 dark:border-stone-600 border p-2.5 text-sm bg-white dark:bg-stone-900 text-stone-800 dark:text-stone-100 placeholder-stone-400 dark:placeholder-stone-500 focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="">-- Seleccionar Producto --</option>
                            @foreach($productos as $prod)
                                <option value="{{ $prod->id }}">{{ $prod->nombre }} (Stock: {{ $prod->stock }} {{ $prod->unidadLabel() }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-stone-600 dark:text-stone-300 uppercase mb-1.5">Cantidad a Descartar</label>
                        <input type="number" name="cantidad" min="0.001" step="0.001" required class="w-full rounded-lg border-stone-300 dark:border-stone-600 border p-2.5 text-sm bg-white dark:bg-stone-900 text-stone-800 dark:text-stone-100 placeholder-stone-400 dark:placeholder-stone-500 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-stone-600 dark:text-stone-300 uppercase mb-1.5">Motivo</label>
                        <select name="motivo" required class="w-full rounded-lg border-stone-300 dark:border-stone-600 border p-2.5 text-sm bg-white dark:bg-stone-900 text-stone-800 dark:text-stone-100 placeholder-stone-400 dark:placeholder-stone-500 focus:ring-emerald-500 focus:border-emerald-500">
                            @foreach(\App\Models\Merma::MOTIVOS as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-stone-600 dark:text-stone-300 uppercase mb-1.5">Observación (Opcional)</label>
                        <textarea name="observacion" rows="2" class="w-full rounded-lg border-stone-300 dark:border-stone-600 border p-2.5 text-sm bg-white dark:bg-stone-900 text-stone-800 dark:text-stone-100 placeholder-stone-400 dark:placeholder-stone-500 focus:ring-emerald-500 focus:border-emerald-500"></textarea>
                    </div>

                    <button type="submit" class="w-full flex items-center justify-center gap-2 bg-rose-600 hover:bg-rose-700 text-white font-semibold py-2.5 px-4 rounded-xl shadow-sm border border-stone-200 dark:border-stone-700 transition text-sm">
                        <x-icon name="trending-down" class="w-4 h-4" /> Registrar y Descontar Stock
                    </button>
                </form>
            </div>

            <!-- Historial -->
            <div class="lg:col-span-2 bg-white dark:bg-stone-800 rounded-2xl shadow-sm border border-stone-200 dark:border-stone-700 p-6">
                <h3 class="font-display text-base font-bold text-stone-800 dark:text-stone-100 mb-4">Historial de Pérdidas</h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-stone-600 dark:text-stone-300">
                        <thead class="text-stone-500 dark:text-stone-400 uppercase text-xs border-b border-stone-200 dark:border-stone-700">
                            <tr>
                                <th class="py-3 px-4 font-semibold">Fecha</th>
                                <th class="py-3 px-4 font-semibold">Producto</th>
                                <th class="py-3 px-4 font-semibold text-center">Cantidad</th>
                                <th class="py-3 px-4 font-semibold">Motivo</th>
                                <th class="py-3 px-4 font-semibold">Registrado Por</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100 dark:divide-stone-700">
                            @forelse($mermas as $merma)
                                <tr class="hover:bg-stone-50 dark:hover:bg-stone-700/40">
                                    <td class="py-3 px-4 text-xs">{{ $merma->fecha->format('d/m/Y H:i') }}</td>
                                    <td class="py-3 px-4 font-bold text-stone-900 dark:text-stone-100">{{ $merma->producto->nombre ?? 'Eliminado' }}</td>
                                    <td class="py-3 px-4 text-center font-bold text-rose-600 dark:text-rose-400">-{{ $merma->cantidad }}</td>
                                    <td class="py-3 px-4 text-xs">
                                        <span class="bg-rose-50 text-rose-800 border border-rose-200 dark:bg-rose-900/40 dark:text-rose-300 dark:border-rose-700 px-2.5 py-1 rounded-md font-semibold">
                                            {{ $merma->motivoLabel() }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-xs">{{ $merma->usuario->name ?? 'Sistema' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-10 text-center text-stone-400 dark:text-stone-500">
                                        <x-icon name="leaf" class="w-8 h-8 mx-auto mb-2 text-stone-300 dark:text-stone-600" />
                                        No hay pérdidas registradas aún.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>
@endsection
