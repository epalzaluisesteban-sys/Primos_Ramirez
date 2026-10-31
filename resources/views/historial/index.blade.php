@extends('layouts.app')

@section('title', 'Historial General')

@section('content')
    <div class="max-w-7xl mx-auto px-4 py-8">

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <div>
                <h2 class="font-display text-xl font-bold text-stone-900 flex items-center gap-2">
                    <x-icon name="clock" class="w-5 h-5 text-stone-500" /> Historial General
                </h2>
                <p class="text-sm text-stone-500 mt-0.5">Bitácora de toda la actividad: ventas, compras, pérdidas, abonos y caja.</p>
            </div>
            <x-acciones-exportar :excel="route('historial.excel', request()->only(['desde','hasta']))" :pdf="route('historial.pdf', request()->only(['desde','hasta']))" />
        </div>

        <!-- Filtro de fechas -->
        <form method="GET" class="bg-white rounded-2xl shadow-sm border border-stone-200 p-4 mb-8 flex flex-wrap items-end gap-4 no-print">
            <div>
                <label class="block text-xs font-bold text-stone-500 uppercase mb-1">Desde</label>
                <input type="date" name="desde" value="{{ $desde }}" class="border-stone-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-stone-500 uppercase mb-1">Hasta</label>
                <input type="date" name="hasta" value="{{ $hasta }}" class="border-stone-300 rounded-lg text-sm">
            </div>
            <button type="submit" class="bg-green-700 hover:bg-green-800 text-white font-bold px-4 py-2 rounded-lg text-sm shadow">
                Filtrar
            </button>
        </form>

        @php
            // Clases completas y literales para que Tailwind (JIT) las detecte al escanear el archivo.
            $colorClasesHistorial = [
                'emerald' => 'bg-emerald-100 text-emerald-600',
                'blue' => 'bg-blue-100 text-blue-600',
                'rose' => 'bg-rose-100 text-rose-600',
                'amber' => 'bg-amber-100 text-amber-600',
                'stone' => 'bg-stone-100 text-stone-600',
            ];
        @endphp

        <div class="bg-white rounded-2xl shadow-sm border border-stone-200 p-6">
            <p class="text-xs text-stone-400 mb-4">{{ $eventos->count() }} evento(s) entre {{ $desde }} y {{ $hasta }}.</p>
            <div class="space-y-1">
                @forelse($eventos as $item)
                    <div class="flex items-center gap-3 py-2.5 px-2 rounded-lg hover:bg-stone-50 border-b border-stone-100">
                        <span class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center {{ $colorClasesHistorial[$item['color']] ?? 'bg-stone-100 text-stone-600' }}">
                            <x-icon name="{{ $item['icono'] }}" class="w-4 h-4" />
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-stone-800 truncate">{{ $item['texto'] }}</p>
                            <p class="text-xs text-stone-400">{{ $item['usuario'] }} · {{ $item['fecha']->format('d/m/Y h:i A') }}</p>
                        </div>
                        @if($item['monto'] !== null)
                            <span class="text-sm font-bold text-stone-700 shrink-0">${{ number_format($item['monto'], 2) }}</span>
                        @endif
                    </div>
                @empty
                    <p class="text-center text-stone-500 py-8">No hay actividad registrada en este período.</p>
                @endforelse
            </div>
        </div>

    </div>
@endsection
