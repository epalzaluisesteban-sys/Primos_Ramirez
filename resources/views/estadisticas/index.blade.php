@extends('layouts.app')

@section('title', 'Estadísticas Avanzadas')

@push('head')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@section('content')
    <div class="max-w-7xl mx-auto px-4 py-8">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="font-display text-xl font-bold text-stone-900">Panel de Estadísticas Avanzado</h2>
                <p class="text-sm text-stone-500 mt-0.5">Tendencias, comparativos y proyección de demanda para apoyar la toma de decisiones.</p>
            </div>
            <a href="{{ route('reportes.ganancias') }}" class="flex items-center gap-1.5 text-sm font-semibold text-emerald-800 hover:underline">
                <x-icon name="chart-bar" class="w-4 h-4" /> Ver Reporte de Ganancias
            </a>
        </div>

        <!-- ============================================ -->
        <!-- Comparación: Mes Actual vs. Mes Anterior -->
        <!-- ============================================ -->
        <div class="bg-white rounded-2xl shadow-sm border border-stone-200 p-6 mb-8">
            <h3 class="font-display text-sm font-bold text-stone-800 mb-4 flex items-center gap-2">
                <x-icon name="trending-up" class="w-4 h-4 text-emerald-600" /> Este Mes vs. Mes Anterior
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <div>
                    <p class="text-xs font-bold text-stone-500 uppercase tracking-wide">Ventas</p>
                    <p class="font-display text-xl font-bold text-stone-800 mt-1">${{ number_format($comparacionMensual['ventas_actual'], 2) }}</p>
                    <p class="text-xs text-stone-400">Mes anterior: ${{ number_format($comparacionMensual['ventas_anterior'], 2) }}</p>
                    @if($comparacionMensual['variacion_ventas'] !== null)
                        <span class="inline-flex items-center gap-1 text-xs font-bold mt-1 {{ $comparacionMensual['variacion_ventas'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            <x-icon name="{{ $comparacionMensual['variacion_ventas'] >= 0 ? 'trending-up' : 'trending-down' }}" class="w-3.5 h-3.5" />
                            {{ number_format(abs($comparacionMensual['variacion_ventas']), 1) }}%
                        </span>
                    @endif
                </div>
                <div>
                    <p class="text-xs font-bold text-stone-500 uppercase tracking-wide">Tickets (Ventas)</p>
                    <p class="font-display text-xl font-bold text-stone-800 mt-1">{{ $comparacionMensual['tickets_actual'] }}</p>
                    <p class="text-xs text-stone-400">Mes anterior: {{ $comparacionMensual['tickets_anterior'] }}</p>
                </div>
                <div>
                    <p class="text-xs font-bold text-stone-500 uppercase tracking-wide">Ticket Promedio</p>
                    <p class="font-display text-xl font-bold text-stone-800 mt-1">${{ number_format($comparacionMensual['ticket_prom_actual'], 2) }}</p>
                    <p class="text-xs text-stone-400">Mes anterior: ${{ number_format($comparacionMensual['ticket_prom_anterior'], 2) }}</p>
                </div>
                <div>
                    <p class="text-xs font-bold text-stone-500 uppercase tracking-wide">Compras</p>
                    <p class="font-display text-xl font-bold text-stone-800 mt-1">${{ number_format($comparacionMensual['compras_actual'], 2) }}</p>
                    <p class="text-xs text-stone-400">Mes anterior: ${{ number_format($comparacionMensual['compras_anterior'], 2) }}</p>
                    @if($comparacionMensual['variacion_compras'] !== null)
                        <span class="inline-flex items-center gap-1 text-xs font-bold mt-1 {{ $comparacionMensual['variacion_compras'] <= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            <x-icon name="{{ $comparacionMensual['variacion_compras'] >= 0 ? 'trending-up' : 'trending-down' }}" class="w-3.5 h-3.5" />
                            {{ number_format(abs($comparacionMensual['variacion_compras']), 1) }}%
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Tarjetas resumen de mermas -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-sm border border-stone-200 p-6 border-t-4 border-rose-500">
                <p class="text-xs font-bold text-stone-500 uppercase tracking-wide">Valor Perdido por Pérdidas de Mercancía (6 meses)</p>
                <p class="font-display text-2xl font-bold text-rose-600 mt-1">${{ number_format($totalMermaValor, 2) }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-stone-200 p-6 border-t-4 border-amber-500">
                <p class="text-xs font-bold text-stone-500 uppercase tracking-wide">Unidades Perdidas (6 meses)</p>
                <p class="font-display text-2xl font-bold text-stone-800 mt-1">{{ $totalMermaUnidades }}</p>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- Tendencia de Ventas por Período + Categoría -->
        <!-- ============================================ -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-stone-200">
                <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
                    <h3 class="font-display text-base font-bold text-stone-800 flex items-center gap-2">
                        <x-icon name="trending-up" class="w-4 h-4 text-emerald-600" /> Tendencia de Ventas
                    </h3>
                    <div class="flex gap-1 bg-stone-100 rounded-full p-1">
                        @foreach(['dia' => 'Día', 'semana' => 'Semana', 'mes' => 'Mes', 'anio' => 'Año'] as $valor => $etiqueta)
                            <a href="{{ route('estadisticas.index', ['agrupacion' => $valor]) }}"
                               class="text-xs font-bold px-3 py-1.5 rounded-full transition {{ $agrupacion === $valor ? 'bg-emerald-700 text-white' : 'text-stone-500 hover:text-stone-800' }}">
                                {{ $etiqueta }}
                            </a>
                        @endforeach
                    </div>
                </div>
                <div class="relative h-64">
                    <canvas id="chartTendencia"></canvas>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-stone-200">
                <h3 class="font-display text-base font-bold text-stone-800 mb-4 flex items-center gap-2">
                    <x-icon name="pie" class="w-4 h-4 text-blue-600" /> Ingresos por Categoría
                </h3>
                <div class="relative h-64">
                    <canvas id="chartCategoria"></canvas>
                </div>
            </div>
        </div>

        <!-- Fila: top proveedores + mermas por mes -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-stone-200">
                <h3 class="font-display text-base font-bold text-stone-800 mb-4 flex items-center gap-2">
                    <x-icon name="truck" class="w-4 h-4 text-indigo-600" /> Top Proveedores por Monto Comprado
                </h3>
                <div class="relative h-64">
                    <canvas id="chartProveedores"></canvas>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-stone-200">
                <h3 class="font-display text-base font-bold text-stone-800 mb-4 flex items-center gap-2">
                    <x-icon name="package-x" class="w-4 h-4 text-rose-600" /> Valor de Pérdidas por Mes
                </h3>
                <div class="relative h-64">
                    <canvas id="chartMermas"></canvas>
                </div>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- Productos más vendidos / menos vendidos -->
        <!-- ============================================ -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <div class="bg-white rounded-2xl shadow-sm border border-stone-200 p-6">
                <h3 class="font-display text-sm font-bold text-stone-800 mb-4 flex items-center gap-2">
                    <x-icon name="crown" class="w-4 h-4 text-amber-500" /> Productos Más Vendidos (histórico)
                </h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-stone-600">
                        <thead class="bg-stone-50 text-stone-600 uppercase text-xs">
                            <tr>
                                <th class="py-2.5 px-3">Producto</th>
                                <th class="py-2.5 px-3 text-center">Unidades</th>
                                <th class="py-2.5 px-3 text-right">Ingreso</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            @forelse($productosMasVendidos as $p)
                                <tr class="hover:bg-stone-50/50">
                                    <td class="py-2.5 px-3 font-bold text-stone-900">{{ $p->producto->nombre ?? 'Eliminado' }}</td>
                                    <td class="py-2.5 px-3 text-center">{{ $p->total_vendido }}</td>
                                    <td class="py-2.5 px-3 text-right font-bold text-green-700">${{ number_format($p->total_ingreso, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="py-4 text-center text-stone-500">Aún no hay ventas registradas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-stone-200 p-6">
                <h3 class="font-display text-sm font-bold text-stone-800 mb-1 flex items-center gap-2">
                    <x-icon name="trending-down" class="w-4 h-4 text-stone-500" /> Productos Menos Vendidos (histórico)
                </h3>
                <p class="text-xs text-stone-400 mb-4">{{ $productosNuncaVendidos }} producto(s) nunca se han vendido (no incluidos abajo).</p>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-stone-600">
                        <thead class="bg-stone-50 text-stone-600 uppercase text-xs">
                            <tr>
                                <th class="py-2.5 px-3">Producto</th>
                                <th class="py-2.5 px-3 text-center">Unidades</th>
                                <th class="py-2.5 px-3 text-right">Ingreso</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            @forelse($productosMenosVendidos as $p)
                                <tr class="hover:bg-stone-50/50">
                                    <td class="py-2.5 px-3 font-bold text-stone-900">{{ $p->producto->nombre ?? 'Eliminado' }}</td>
                                    <td class="py-2.5 px-3 text-center">{{ $p->total_vendido }}</td>
                                    <td class="py-2.5 px-3 text-right font-bold text-stone-600">${{ number_format($p->total_ingreso, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="py-4 text-center text-stone-500">Aún no hay ventas registradas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- Clientes que más compran / que dejaron de comprar -->
        <!-- ============================================ -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <div class="bg-white rounded-2xl shadow-sm border border-stone-200 p-6">
                <h3 class="font-display text-sm font-bold text-stone-800 mb-4 flex items-center gap-2">
                    <x-icon name="users" class="w-4 h-4 text-blue-500" /> Clientes que Más Compran
                </h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-stone-600">
                        <thead class="bg-stone-50 text-stone-600 uppercase text-xs">
                            <tr>
                                <th class="py-2.5 px-3">Cliente</th>
                                <th class="py-2.5 px-3 text-center">Compras</th>
                                <th class="py-2.5 px-3 text-right">Ticket Prom.</th>
                                <th class="py-2.5 px-3 text-right">Total Gastado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            @forelse($clientesFrecuentes as $cl)
                                <tr class="hover:bg-stone-50/50">
                                    <td class="py-2.5 px-3 font-bold text-stone-900">{{ $cl->nombre }}</td>
                                    <td class="py-2.5 px-3 text-center">{{ $cl->ventas_count }}</td>
                                    <td class="py-2.5 px-3 text-right">${{ number_format($cl->ticket_promedio, 2) }}</td>
                                    <td class="py-2.5 px-3 text-right font-bold text-green-700">${{ number_format($cl->ventas_sum_total ?? 0, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-4 text-center text-stone-500">Aún no hay clientes con compras registradas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-stone-200 p-6">
                <h3 class="font-display text-sm font-bold text-stone-800 mb-1 flex items-center gap-2">
                    <x-icon name="ban" class="w-4 h-4 text-rose-500" /> Clientes que Dejaron de Comprar
                </h3>
                <p class="text-xs text-stone-400 mb-4">Clientes activos sin compras en los últimos {{ \App\Http\Controllers\EstadisticaController::DIAS_INACTIVIDAD_CLIENTE }} días.</p>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-stone-600">
                        <thead class="bg-stone-50 text-stone-600 uppercase text-xs">
                            <tr>
                                <th class="py-2.5 px-3">Cliente</th>
                                <th class="py-2.5 px-3 text-center">Días sin comprar</th>
                                <th class="py-2.5 px-3 text-right">Saldo Pendiente</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            @forelse($clientesInactivos as $cl)
                                <tr class="hover:bg-rose-50/50">
                                    <td class="py-2.5 px-3 font-bold text-stone-900">{{ $cl->nombre }}</td>
                                    <td class="py-2.5 px-3 text-center">
                                        <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-rose-100 text-rose-800 border border-rose-300">
                                            {{ $cl->dias_sin_comprar }} días
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-bold {{ $cl->saldo > 0 ? 'text-amber-600' : 'text-stone-400' }}">
                                        ${{ number_format($cl->saldo, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="py-4 text-center text-stone-500">Todos los clientes activos han comprado recientemente.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Predicción de demanda -->
        <div class="bg-white rounded-2xl shadow-sm border border-stone-200 p-6">
            <h3 class="font-display text-base font-bold text-stone-800 mb-1 flex items-center gap-2">
                <x-icon name="trending-up" class="w-4 h-4 text-emerald-600" /> Predicción Simple de Demanda — Próxima Semana
            </h3>
            <p class="text-xs text-stone-500 mb-4">Basada en el promedio móvil de ventas de las últimas 4 semanas para los 10 productos con más movimiento.</p>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-stone-600">
                    <thead class="text-stone-500 uppercase text-xs border-b border-stone-200">
                        <tr>
                            <th class="py-3 px-4 font-semibold">Producto</th>
                            <th class="py-3 px-4 font-semibold text-center">Vendido (4 semanas)</th>
                            <th class="py-3 px-4 font-semibold text-center">Promedio Semanal</th>
                            <th class="py-3 px-4 font-semibold text-center">Proyección Próx. Semana</th>
                            <th class="py-3 px-4 font-semibold text-center">Stock Actual</th>
                            <th class="py-3 px-4 font-semibold text-center">¿Stock Suficiente?</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @forelse($prediccion as $item)
                            <tr class="hover:bg-stone-50">
                                <td class="py-3 px-4 font-bold text-stone-900">{{ $item['producto']->nombre }}</td>
                                <td class="py-3 px-4 text-center">{{ $item['vendido_4_semanas'] }}</td>
                                <td class="py-3 px-4 text-center">{{ $item['promedio_semanal'] }}</td>
                                <td class="py-3 px-4 text-center font-bold text-blue-800">{{ $item['proyeccion_proxima_semana'] }} {{ $item['producto']->unidadLabel() }}</td>
                                <td class="py-3 px-4 text-center">{{ $item['producto']->stock }}</td>
                                <td class="py-3 px-4 text-center">
                                    @if($item['cubre_con_stock'])
                                        <span class="bg-emerald-100 text-emerald-800 font-bold text-xs px-3 py-1 rounded-full border border-emerald-300">Sí alcanza</span>
                                    @else
                                        <span class="bg-rose-100 text-rose-800 font-bold text-xs px-3 py-1 rounded-full border border-rose-300">Reabastecer</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-stone-500">Aún no hay suficientes ventas registradas para proyectar demanda.</td>
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

        // Tendencia de ventas por período (día/semana/mes/año)
        new Chart(document.getElementById('chartTendencia').getContext('2d'), {
            type: 'line',
            data: {
                labels: @json($ventasPorPeriodo->pluck('etiqueta')),
                datasets: [{
                    label: 'Ingresos ($)',
                    data: @json($ventasPorPeriodo->pluck('total')),
                    borderColor: '#15803d',
                    backgroundColor: 'rgba(21, 128, 61, 0.1)',
                    fill: true,
                    tension: 0.3,
                    borderWidth: 2,
                    pointBackgroundColor: '#15803d'
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });

        // Ingresos por categoría
        new Chart(document.getElementById('chartCategoria').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: @json($porCategoria->pluck('categoria')),
                datasets: [{
                    data: @json($porCategoria->pluck('ingreso')),
                    backgroundColor: ['#16a34a', '#2563eb', '#d97706', '#dc2626', '#9333ea', '#0891b2']
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });

        // Top proveedores
        new Chart(document.getElementById('chartProveedores').getContext('2d'), {
            type: 'bar',
            data: {
                labels: @json($topProveedores->pluck('nombre_proveedor')),
                datasets: [{
                    label: 'Total Comprado ($)',
                    data: @json($topProveedores->pluck('total_comprado')),
                    backgroundColor: '#4f46e5',
                    borderRadius: 6
                }]
            },
            options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
        });

        // Mermas por mes
        new Chart(document.getElementById('chartMermas').getContext('2d'), {
            type: 'bar',
            data: {
                labels: @json($mermasPorMes->keys()),
                datasets: [{
                    label: 'Valor Perdido ($)',
                    data: @json($mermasPorMes->pluck('valor')),
                    backgroundColor: '#e11d48',
                    borderRadius: 6
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });
    </script>
@endpush
