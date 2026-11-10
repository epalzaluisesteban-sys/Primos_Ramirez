@extends('layouts.app')

@section('title', 'Historial de movimientos')

@section('content')
    <div class="pagina">

        <x-encabezado titulo="Historial de movimientos" subtitulo="Todo lo que se hizo en el sistema: ventas, compras, pérdidas, abonos y caja.">
            <x-acciones-exportar :excel="route('historial.excel', request()->only(['desde','hasta']))" :pdf="route('historial.pdf', request()->only(['desde','hasta']))" />
        </x-encabezado>

        <!-- Filtro de fechas -->
        <form method="GET" class="tarjeta p-4 mb-8 flex flex-wrap items-end gap-4 no-print">
            <div>
                <label class="etiqueta">Desde</label>
                <input type="date" name="desde" value="{{ $desde }}" class="campo">
            </div>
            <div>
                <label class="etiqueta">Hasta</label>
                <input type="date" name="hasta" value="{{ $hasta }}" class="campo">
            </div>
            <button type="submit" class="btn-oscuro">
                Filtrar
            </button>
        </form>

        @php
            // Clases completas y literales para que Tailwind (JIT) las detecte al escanear el archivo.
            $colorClasesHistorial = [
                'emerald' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-300',
                'blue' => 'bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-300',
                'rose' => 'bg-rose-100 text-rose-600 dark:bg-rose-900/40 dark:text-rose-300',
                'amber' => 'bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-300',
                'stone' => 'bg-stone-100 text-stone-600 dark:bg-stone-700 dark:text-stone-300',
            ];
        @endphp

        <div class="tarjeta">
            <p class="text-xs text-stone-400 mb-4 dark:text-stone-500">{{ $eventos->count() }} evento(s) entre {{ $desde }} y {{ $hasta }}.</p>
            <div class="space-y-1">
                @forelse($eventos as $item)
                    <div class="flex items-center gap-3 py-2.5 px-2 rounded-md hover:bg-stone-50 border-b border-stone-100 dark:hover:bg-stone-700/40 dark:border-stone-700/60">
                        <span class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center {{ $colorClasesHistorial[$item['color']] ?? 'bg-stone-100 text-stone-600 dark:bg-stone-700 dark:text-stone-300' }}">
                            <x-icon name="{{ $item['icono'] }}" class="w-4 h-4" />
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-stone-800 truncate dark:text-stone-100">{{ $item['texto'] }}</p>
                            <p class="text-xs text-stone-400 dark:text-stone-500">{{ $item['usuario'] }} · {{ $item['fecha']->format('d/m/Y h:i A') }}</p>
                        </div>
                        @if($item['monto'] !== null)
                            <span class="text-sm font-bold text-stone-700 shrink-0 dark:text-stone-200">${{ number_format($item['monto'], 2) }}</span>
                        @endif
                    </div>
                @empty
                    <p class="text-center text-stone-500 py-8 dark:text-stone-400">No hay actividad registrada en este período.</p>
                @endforelse
            </div>
        </div>

    </div>
@endsection
