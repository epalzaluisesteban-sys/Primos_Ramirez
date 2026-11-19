@extends('layouts.app')

@section('title', 'Ganancias')

@push('head')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@section('content')
    <div class="pagina">

        <x-encabezado titulo="Ganancias" subtitulo="Utilidad, margen y comparativos del negocio en el periodo elegido.">
            @php $filtros = array_filter(request()->only(['desde', 'hasta', 'categoria_id'])); @endphp
            <x-acciones-exportar :excel="route('reportes.ganancias.excel', $filtros)" :pdf="route('reportes.ganancias.pdf', $filtros)" />
        </x-encabezado>

        @if($hayCostoDesconocido)
            <div class="aviso-ambar mb-6">
                <x-icon name="alert-triangle" class="w-4 h-4 mt-0.5 shrink-0 text-amber-500" />
                <span>
                    Algunos productos no tienen ninguna compra registrada, por lo que su costo se
                    desconoce (se muestran marcados como "Costo N/D"). Lo vendido de esos productos
                    (<strong>@usd($ingresoSinCosto)</strong>) no se cuenta en la utilidad ni en el margen.
                    Registra una entrada en <strong>Compras</strong> para que su margen se calcule correctamente.
                </span>
            </div>
        @endif

        <x-filtros id="filtros-ganancias" :activos="count($filtros)" class="tarjeta p-4 mb-4 sm:mb-8 no-print">
        <form method="GET" class="grid grid-cols-2 sm:flex sm:flex-wrap items-end gap-3 sm:gap-4">
            <div>
                <label class="etiqueta" for="ganancias-desde">Desde</label>
                <input type="date" id="ganancias-desde" name="desde" value="{{ $desde }}" class="campo">
            </div>
            <div>
                <label class="etiqueta" for="ganancias-hasta">Hasta</label>
                <input type="date" id="ganancias-hasta" name="hasta" value="{{ $hasta }}" class="campo @error('hasta') border-rose-500 @enderror">
                @error('hasta') <p class="error-campo">{{ $message }}</p> @enderror
            </div>
            <x-selector-categoria :categorias="$categorias" :valor="$categoria?->id" id="ganancias-categoria" envoltura="col-span-2" />
            <button type="submit" class="btn-oscuro">
                <x-icon name="search" class="w-4 h-4" /> Filtrar
            </button>
            @if($filtros)
                <a href="{{ route('reportes.ganancias') }}" class="btn-secundario">Quitar filtro</a>
            @endif
        </form>
        </x-filtros>

        <!-- KPIs -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-4 sm:mb-8">
            <div class="tarjeta p-4">
                <p class="cifra-etiqueta">Ingresos</p>
                <x-precio :usd="$ingresoTotal" class="cifra-valor" />
            </div>
            <div class="tarjeta p-4">
                <p class="cifra-etiqueta">Costo de mercancía vendida</p>
                <x-precio :usd="$costoTotal" class="cifra-valor" />
            </div>
            <div class="tarjeta p-4">
                <p class="cifra-etiqueta">Utilidad bruta</p>
                <x-precio :usd="$utilidadTotal" class="cifra-valor {{ $utilidadTotal >= 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}" />
            </div>
            <div class="tarjeta p-4">
                <p class="cifra-etiqueta">Margen de ganancia</p>
                <p class="cifra-valor">{{ number_format($margenTotal, 1) }}%</p>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <div class="tarjeta">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="titulo-tarjeta">Comparativo de ventas mensuales
                    </h3>
                    @if($variacionMensual !== null)
                        <span class="text-xs font-bold px-2 py-1 rounded {{ $variacionMensual >= 0 ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' : 'bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-300' }}">
                            {{ $variacionMensual >= 0 ? '▲' : '▼' }} {{ number_format(abs($variacionMensual), 1) }}% vs mes anterior
                        </span>
                    @endif
                </div>
                <div class="relative h-64">
                    <canvas id="chartMensual"></canvas>
                </div>
            </div>

            <div class="tarjeta">
                <h3 class="titulo-tarjeta mb-4">Ingresos por categoría
                </h3>
                <div class="relative h-64">
                    <canvas id="chartCategoria"></canvas>
                </div>
            </div>
        </div>

        <!-- Tabla de rentabilidad por producto -->
        <div class="tarjeta">
            <h3 class="titulo-tarjeta mb-4">Rentabilidad por producto
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-stone-600 dark:text-stone-300">
                    <thead class="bg-stone-50 text-stone-600 uppercase text-xs dark:bg-stone-900/60 dark:text-stone-300">
                        <tr>
                            <th class="py-3 px-3">Producto</th>
                            <th class="py-3 px-3 text-center">Unidades vendidas</th>
                            <th class="py-3 px-3 text-right">Ingresos</th>
                            <th class="py-3 px-3 text-right">Costo</th>
                            <th class="py-3 px-3 text-right">Utilidad</th>
                            <th class="py-3 px-3 text-right">Margen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 dark:divide-stone-700/60">
                        @forelse($porProducto as $nombre => $p)
                            <tr class="hover:bg-stone-50/50 dark:hover:bg-stone-700/40">
                                <td class="py-3 px-3 font-semibold text-stone-800 dark:text-stone-100">{{ $nombre }}</td>
                                <td class="py-3 px-3 text-center">{{ \App\Support\Cantidad::formato($p['cantidad']) }}</td>
                                <td class="py-3 px-3 text-right font-bold text-blue-800 dark:text-blue-300">${{ number_format($p['ingreso'], 2) }}</td>
                                <td class="py-3 px-3 text-right">
                                    @if($p['costo_conocido'])
                                        ${{ number_format($p['costo'], 2) }}
                                    @else
                                        <span class="text-amber-600 font-semibold text-xs dark:text-amber-400">Costo N/D</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 text-right font-bold {{ $p['utilidad'] >= 0 ? 'text-green-700 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    @if($p['costo_conocido']) ${{ number_format($p['utilidad'], 2) }} @else — @endif
                                </td>
                                <td class="py-3 px-3 text-right">
                                    @if($p['costo_conocido'])
                                        {{ number_format($p['margen'], 1) }}%
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-4 text-center text-stone-500 dark:text-stone-400">No hay ventas registradas en este período.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tabla de rentabilidad por categoría -->
        <div class="tarjeta mt-8">
            <h3 class="titulo-tarjeta mb-4">Rentabilidad por categoría
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-stone-600 dark:text-stone-300">
                    <thead class="bg-stone-50 text-stone-600 uppercase text-xs dark:bg-stone-900/60 dark:text-stone-300">
                        <tr>
                            <th class="py-3 px-3">Categoría</th>
                            <th class="py-3 px-3 text-center">Unidades vendidas</th>
                            <th class="py-3 px-3 text-right">Ingresos</th>
                            <th class="py-3 px-3 text-right">Costo</th>
                            <th class="py-3 px-3 text-right">Utilidad</th>
                            <th class="py-3 px-3 text-right">Margen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 dark:divide-stone-700/60">
                        @forelse($porCategoria as $nombre => $c)
                            <tr class="hover:bg-stone-50/50 dark:hover:bg-stone-700/40">
                                <td class="py-3 px-3 font-semibold text-stone-800 dark:text-stone-100">{{ $nombre }}</td>
                                <td class="py-3 px-3 text-center">{{ \App\Support\Cantidad::formato($c['cantidad']) }}</td>
                                <td class="py-3 px-3 text-right font-bold text-blue-800 dark:text-blue-300">${{ number_format($c['ingreso'], 2) }}</td>
                                <td class="py-3 px-3 text-right">
                                    @if($c['costo_conocido'])
                                        ${{ number_format($c['costo'], 2) }}
                                    @else
                                        <span class="text-amber-600 font-semibold text-xs dark:text-amber-400">Costo N/D</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 text-right font-bold {{ $c['utilidad'] >= 0 ? 'text-green-700 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    @if($c['costo_conocido']) ${{ number_format($c['utilidad'], 2) }} @else — @endif
                                </td>
                                <td class="py-3 px-3 text-right">
                                    @if($c['costo_conocido'])
                                        {{ number_format($c['margen'], 1) }}%
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-4 text-center text-stone-500 dark:text-stone-400">No hay ventas registradas en este período.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        // Colores por defecto de Chart.js ajustados para el fondo oscuro
        Chart.defaults.color = '#78716c';
        Chart.defaults.borderColor = '#e7e5e4';

        const ctxMensual = document.getElementById('chartMensual').getContext('2d');
        new Chart(ctxMensual, {
            type: 'bar',
            data: {
                labels: @json($ventasPorMes->pluck('mes')),
                datasets: [{
                    label: 'Ventas ($)',
                    data: @json($ventasPorMes->pluck('total')),
                    backgroundColor: '#15803d',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });

        const ctxCategoria = document.getElementById('chartCategoria').getContext('2d');
        new Chart(ctxCategoria, {
            type: 'doughnut',
            data: {
                labels: @json(array_keys($porCategoria)),
                datasets: [{
                    data: @json(array_column($porCategoria, 'ingreso')),
                    backgroundColor: ['#16a34a', '#2563eb', '#d97706', '#9333ea', '#dc2626']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    </script>
@endpush
