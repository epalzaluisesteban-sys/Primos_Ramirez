@extends('layouts.app')

@section('title', 'Reporte de Ganancias')

@push('head')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@section('content')
    <div class="max-w-7xl mx-auto px-4 py-8">

        <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-display text-xl font-bold text-stone-800 flex items-center gap-2">
                    <x-icon name="chart-bar" class="w-5 h-5 text-emerald-800" /> Reporte de Ganancias
                </h2>
                <p class="text-sm text-stone-500">Utilidad, márgen y comparativos del negocio.</p>
            </div>
            <x-acciones-exportar :excel="route('reportes.ganancias.excel', request()->only(['desde','hasta']))" :pdf="route('reportes.ganancias.pdf', request()->only(['desde','hasta']))" />
        </div>

        @if($hayCostoDesconocido)
            <div class="flex items-start gap-3 bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-xl mb-6 text-sm">
                <x-icon name="alert-triangle" class="w-4 h-4 mt-0.5 shrink-0 text-amber-500" />
                <span>
                    Algunos productos no tienen ninguna compra registrada, por lo que su costo se
                    desconoce y no se incluye en el cálculo de utilidad (se muestran marcados como "Costo N/D").
                    Registra una entrada en <strong>Compras</strong> para que su margen se calcule correctamente.
                </span>
            </div>
        @endif

        <!-- Filtro de fechas -->
        <form method="GET" class="bg-white rounded-2xl shadow-sm border border-stone-200 p-4 mb-8 flex flex-wrap items-end gap-4">
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
            @if($desde || $hasta)
                <a href="{{ route('reportes.ganancias') }}" class="text-sm text-stone-500 hover:underline">Limpiar filtro</a>
            @endif
        </form>

        <!-- KPIs -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-sm hover:shadow-md transition border border-stone-200 p-6 border-t-4 border-blue-600">
                <p class="text-xs font-bold text-stone-500 uppercase">Ingresos</p>
                <x-precio :usd="$ingresoTotal" class="mt-1 text-2xl font-black text-stone-800" />
            </div>
            <div class="bg-white rounded-2xl shadow-sm hover:shadow-md transition border border-stone-200 p-6 border-t-4 border-amber-500">
                <p class="text-xs font-bold text-stone-500 uppercase">Costo de Mercancía Vendida</p>
                <x-precio :usd="$costoTotal" class="mt-1 text-2xl font-black text-stone-800" />
            </div>
            <div class="bg-white rounded-2xl shadow-sm hover:shadow-md transition border border-stone-200 p-6 border-t-4 border-green-600">
                <p class="text-xs font-bold text-stone-500 uppercase">Utilidad Bruta</p>
                <p class="text-2xl font-black {{ $utilidadTotal >= 0 ? 'text-green-700' : 'text-red-600' }} mt-1">
                    <x-precio :usd="$utilidadTotal" />
                </p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm hover:shadow-md transition border border-stone-200 p-6 border-t-4 border-purple-600">
                <p class="text-xs font-bold text-stone-500 uppercase">Margen de Ganancia</p>
                <p class="text-2xl font-black text-stone-800 mt-1">{{ number_format($margenTotal, 1) }}%</p>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <div class="bg-white p-6 rounded-2xl shadow-sm hover:shadow-md transition border border-stone-200">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-display text-base font-bold text-stone-800 flex items-center gap-2">
                        <x-icon name="trending-up" class="w-4 h-4 text-emerald-600" /> Comparativo de Ventas Mensuales
                    </h3>
                    @if($variacionMensual !== null)
                        <span class="text-xs font-bold px-2 py-1 rounded-full {{ $variacionMensual >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' }}">
                            {{ $variacionMensual >= 0 ? '▲' : '▼' }} {{ number_format(abs($variacionMensual), 1) }}% vs mes anterior
                        </span>
                    @endif
                </div>
                <div class="relative h-64">
                    <canvas id="chartMensual"></canvas>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm hover:shadow-md transition border border-stone-200">
                <h3 class="font-display text-base font-bold text-stone-800 mb-4 flex items-center gap-2">
                    <x-icon name="pie" class="w-4 h-4 text-blue-600" /> Ingresos por Categoría
                </h3>
                <div class="relative h-64">
                    <canvas id="chartCategoria"></canvas>
                </div>
            </div>
        </div>

        <!-- Tabla de rentabilidad por producto -->
        <div class="bg-white rounded-2xl shadow-sm hover:shadow-md transition border border-stone-200 p-6">
            <h3 class="font-display text-base font-bold text-stone-800 mb-4 flex items-center gap-2">
                <x-icon name="chart-bar" class="w-4 h-4 text-emerald-600" /> Rentabilidad por Producto
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-stone-600">
                    <thead class="bg-stone-50 text-stone-600 uppercase text-xs">
                        <tr>
                            <th class="py-3 px-3">Producto</th>
                            <th class="py-3 px-3 text-center">Unidades Vendidas</th>
                            <th class="py-3 px-3 text-right">Ingresos</th>
                            <th class="py-3 px-3 text-right">Costo</th>
                            <th class="py-3 px-3 text-right">Utilidad</th>
                            <th class="py-3 px-3 text-right">Margen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @forelse($porProducto as $nombre => $p)
                            <tr class="hover:bg-stone-50/50">
                                <td class="py-3 px-3 font-semibold text-stone-800">{{ $nombre }}</td>
                                <td class="py-3 px-3 text-center">{{ $p['cantidad'] }}</td>
                                <td class="py-3 px-3 text-right font-bold text-blue-800">${{ number_format($p['ingreso'], 2) }}</td>
                                <td class="py-3 px-3 text-right">
                                    @if($p['costo_conocido'])
                                        ${{ number_format($p['costo'], 2) }}
                                    @else
                                        <span class="text-amber-600 font-semibold text-xs">Costo N/D</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 text-right font-bold {{ $p['utilidad'] >= 0 ? 'text-green-700' : 'text-red-600' }}">
                                    ${{ number_format($p['utilidad'], 2) }}
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
                                <td colspan="6" class="py-4 text-center text-stone-500">No hay ventas registradas en este período.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tabla de rentabilidad por categoría -->
        <div class="bg-white rounded-2xl shadow-sm hover:shadow-md transition border border-stone-200 p-6 mt-8">
            <h3 class="font-display text-base font-bold text-stone-800 mb-4 flex items-center gap-2">
                <x-icon name="pie" class="w-4 h-4 text-blue-600" /> Rentabilidad por Categoría
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-stone-600">
                    <thead class="bg-stone-50 text-stone-600 uppercase text-xs">
                        <tr>
                            <th class="py-3 px-3">Categoría</th>
                            <th class="py-3 px-3 text-center">Unidades Vendidas</th>
                            <th class="py-3 px-3 text-right">Ingresos</th>
                            <th class="py-3 px-3 text-right">Costo</th>
                            <th class="py-3 px-3 text-right">Utilidad</th>
                            <th class="py-3 px-3 text-right">Margen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @forelse($porCategoria as $nombre => $c)
                            <tr class="hover:bg-stone-50/50">
                                <td class="py-3 px-3 font-semibold text-stone-800">{{ $nombre }}</td>
                                <td class="py-3 px-3 text-center">{{ $c['cantidad'] }}</td>
                                <td class="py-3 px-3 text-right font-bold text-blue-800">${{ number_format($c['ingreso'], 2) }}</td>
                                <td class="py-3 px-3 text-right">
                                    @if($c['costo_conocido'])
                                        ${{ number_format($c['costo'], 2) }}
                                    @else
                                        <span class="text-amber-600 font-semibold text-xs">Costo N/D</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 text-right font-bold {{ $c['utilidad'] >= 0 ? 'text-green-700' : 'text-red-600' }}">
                                    ${{ number_format($c['utilidad'], 2) }}
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
                                <td colspan="6" class="py-4 text-center text-stone-500">No hay ventas registradas en este período.</td>
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
