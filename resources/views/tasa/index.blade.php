@extends('layouts.app')

@section('title', 'Tasa del dólar (BCV)')

@section('content')
    <div class="pagina">

        <x-encabezado titulo="Tasa del dólar (BCV)" subtitulo="Todos los precios se llevan en dólares ($) y se muestran en bolívares (Bs.) a la tasa oficial del Banco Central de Venezuela." />

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <div class="lg:col-span-1 space-y-6">
                <div class="tarjeta">
                    <p class="cifra-etiqueta">Tasa vigente</p>
                    @if($actual)
                        <p class="font-display text-3xl font-extrabold text-emerald-800 dark:text-emerald-400 mt-1">Bs. {{ number_format($actual->tasa, 4, ',', '.') }}</p>
                        <p class="text-sm text-stone-600 dark:text-stone-300 mt-0.5">por cada $1</p>
                        <p class="text-xs text-stone-500 dark:text-stone-400 mt-3">
                            {{ $actual->fecha->format('d/m/Y') }} ·
                            {{ $actual->esAutomatica() ? 'Obtenida del BCV' : 'Cargada manualmente' }}
                        </p>
                    @else
                        <p class="text-sm text-rose-600 dark:text-rose-400 mt-2">Aún no hay una tasa cargada. Actualiza desde el BCV o escríbela a mano.</p>
                    @endif

                    <form action="{{ route('tasa.actualizar') }}" method="POST" class="mt-5">
                        @csrf
                        <button type="submit" class="btn-primario w-full">
                            <x-icon name="download" class="w-4 h-4" /> Actualizar desde el BCV
                        </button>
                    </form>
                    <p class="text-[11px] text-stone-400 dark:text-stone-500 mt-2">Se actualiza sola una vez al día cuando alguien abre el sistema con internet.</p>
                </div>

                <div class="tarjeta">
                    <h3 class="titulo-tarjeta mb-1">Fijar tasa manualmente</h3>
                    <p class="text-xs text-stone-500 dark:text-stone-400 mb-4">Úsala si no hay internet. Reemplaza la tasa de hoy.</p>
                    <form action="{{ route('tasa.store') }}" method="POST" class="space-y-3">
                        @csrf
                        <label class="block text-xs font-semibold text-stone-600 dark:text-stone-300 uppercase">Bs. por $1</label>
                        <input type="number" name="tasa" step="0.0001" min="0.0001" required placeholder="Ej. 889,2553"
                               class="campo font-bold">
                        <button type="submit" class="btn-oscuro w-full">
                            <x-icon name="check" class="w-4 h-4" /> Guardar tasa
                        </button>
                    </form>
                </div>
            </div>

            <div class="tarjeta lg:col-span-2">
                <h3 class="titulo-tarjeta mb-4">Historial de tasas</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-stone-600 dark:text-stone-300">
                        <thead class="text-stone-500 dark:text-stone-400 uppercase text-xs border-b border-stone-200 dark:border-stone-700">
                            <tr>
                                <th class="py-3 px-4 font-semibold">Fecha</th>
                                <th class="py-3 px-4 font-semibold text-right">Bs. por $1</th>
                                <th class="py-3 px-4 font-semibold">Origen</th>
                                <th class="py-3 px-4 font-semibold">Registrada por</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100 dark:divide-stone-700">
                            @forelse($historial as $t)
                                <tr class="hover:bg-stone-50 dark:hover:bg-stone-700/40">
                                    <td class="py-3 px-4 font-semibold text-stone-800 dark:text-stone-100">{{ $t->fecha->format('d/m/Y') }}</td>
                                    <td class="py-3 px-4 text-right font-bold">Bs. {{ number_format($t->tasa, 4, ',', '.') }}</td>
                                    <td class="py-3 px-4">
                                        <span class="text-xs font-bold px-2.5 py-1 rounded {{ $t->esAutomatica() ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' }}">
                                            {{ $t->esAutomatica() ? 'BCV' : 'Manual' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-xs">{{ $t->usuario->name ?? 'Automático' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-6 text-center text-stone-500 dark:text-stone-400">Sin registros todavía.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
