@extends('layouts.app')

@section('title', 'Estadísticas')

@push('head')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@section('content')
    <div class="pagina">

        <x-encabezado titulo="Estadísticas" subtitulo="Tendencias, comparación con el mes anterior y proyección de demanda.">
            <a href="{{ route('reportes.ganancias') }}" class="btn-secundario"><x-icon name="chart-bar" class="w-4 h-4" /> Ver ganancias</a>
        </x-encabezado>

        <!-- ============================================ -->
        <!-- Comparación: Mes Actual vs. Mes Anterior -->
        <!-- ============================================ -->
        <div class="tarjeta mb-8">
            <h3 class="titulo-tarjeta mb-4">Este mes vs. mes anterior
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <div>
                    <p class="cifra-etiqueta">Ventas</p>
                    <p class="font-display text-xl font-bold text-stone-800 mt-1 dark:text-stone-100">${{ number_format($comparacionMensual['ventas_actual'], 2) }}</p>
                    <p class="text-xs text-stone-400 dark:text-stone-500">Mes anterior: ${{ number_format($comparacionMensual['ventas_anterior'], 2) }}</p>
                    @if($comparacionMensual['variacion_ventas'] !== null)
                        <span class="inline-flex items-center gap-1 text-xs font-bold mt-1 {{ $comparacionMensual['variacion_ventas'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                            <x-icon name="{{ $comparacionMensual['variacion_ventas'] >= 0 ? 'trending-up' : 'trending-down' }}" class="w-3.5 h-3.5" />
                            {{ number_format(abs($comparacionMensual['variacion_ventas']), 1) }}%
                        </span>
                    @endif
                </div>
                <div>
                    <p class="cifra-etiqueta">Tickets (Ventas)</p>
                    <p class="font-display text-xl font-bold text-stone-800 mt-1 dark:text-stone-100">{{ $comparacionMensual['tickets_actual'] }}</p>
                    <p class="text-xs text-stone-400 dark:text-stone-500">Mes anterior: {{ $comparacionMensual['tickets_anterior'] }}</p>
                </div>
                <div>
                    <p class="cifra-etiqueta">Ticket promedio</p>
                    <p class="font-display text-xl font-bold text-stone-800 mt-1 dark:text-stone-100">${{ number_format($comparacionMensual['ticket_prom_actual'], 2) }}</p>
                    <p class="text-xs text-stone-400 dark:text-stone-500">Mes anterior: ${{ number_format($comparacionMensual['ticket_prom_anterior'], 2) }}</p>
                </div>
                <div>
                    <p class="cifra-etiqueta">Compras</p>
                    <p class="font-display text-xl font-bold text-stone-800 mt-1 dark:text-stone-100">${{ number_format($comparacionMensual['compras_actual'], 2) }}</p>
                    <p class="text-xs text-stone-400 dark:text-stone-500">Mes anterior: ${{ number_format($comparacionMensual['compras_anterior'], 2) }}</p>
                    @if($comparacionMensual['variacion_compras'] !== null)
                        <span class="inline-flex items-center gap-1 text-xs font-bold mt-1 {{ $comparacionMensual['variacion_compras'] <= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                            <x-icon name="{{ $comparacionMensual['variacion_compras'] >= 0 ? 'trending-up' : 'trending-down' }}" class="w-3.5 h-3.5" />
                            {{ number_format(abs($comparacionMensual['variacion_compras']), 1) }}%
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Tarjetas resumen de mermas -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
            <div class="tarjeta">
                <p class="cifra-etiqueta">Valor perdido por pérdidas de mercancía (6 meses)</p>
                <p class="cifra-valor text-rose-600">${{ number_format($totalMermaValor, 2) }}</p>
            </div>
            <div class="tarjeta">
                <p class="cifra-etiqueta">Unidades perdidas (6 meses)</p>
                <p class="font-display text-2xl font-bold text-stone-800 mt-1 dark:text-stone-100">{{ $totalMermaUnidades }}</p>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- Tendencia de Ventas por Período + Categoría -->
        <!-- ============================================ -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <div class="tarjeta">
                <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
                    <h3 class="titulo-tarjeta">Tendencia de ventas
                    </h3>
                    <div class="flex gap-1 bg-stone-100 rounded p-1 dark:bg-stone-700">
                        @foreach(['dia' => 'Día', 'semana' => 'Semana', 'mes' => 'Mes', 'anio' => 'Año'] as $valor => $etiqueta)
                            <a href="{{ route('estadisticas.index', ['agrupacion' => $valor]) }}"
                               class="text-xs font-bold px-3 py-1.5 rounded transition {{ $agrupacion === $valor ? 'bg-emerald-700 text-white' : 'text-stone-500 hover:text-stone-800 dark:text-stone-400 dark:hover:text-stone-100' }}">
                                {{ $etiqueta }}
                            </a>
                        @endforeach
                    </div>
                </div>
                <div class="relative h-64">
                    <canvas id="chartTendencia"></canvas>
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

        <!-- Fila: top proveedores + mermas por mes -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <div class="tarjeta">
                <h3 class="titulo-tarjeta mb-4">Top proveedores por monto comprado
                </h3>
                <div class="relative h-64">
                    <canvas id="chartProveedores"></canvas>
                </div>
            </div>

            <div class="tarjeta">
                <h3 class="titulo-tarjeta mb-4">Valor de pérdidas por mes
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
            <div class="tarjeta">
                <h3 class="titulo-tarjeta mb-4">Productos más vendidos (histórico)
                </h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-stone-600 dark:text-stone-300">
                        <thead class="bg-stone-50 text-stone-600 uppercase text-xs dark:bg-stone-900/60 dark:text-stone-300">
                            <tr>
                                <th class="py-2.5 px-3">Producto</th>
                                <th class="py-2.5 px-3 text-center">Unidades</th>
                                <th class="py-2.5 px-3 text-right">Ingreso</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100 dark:divide-stone-700/60">
                            @forelse($productosMasVendidos as $p)
                                <tr class="hover:bg-stone-50/50 dark:hover:bg-stone-700/40">
                                    <td class="py-2.5 px-3 font-bold text-stone-900 dark:text-stone-100">{{ $p->producto->nombre ?? 'Eliminado' }}</td>
                                    <td class="py-2.5 px-3 text-center">{{ $p->total_vendido }}</td>
                                    <td class="py-2.5 px-3 text-right font-bold text-green-700 dark:text-green-400">${{ number_format($p->total_ingreso, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="py-4 text-center text-stone-500 dark:text-stone-400">Aún no hay ventas registradas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tarjeta">
                <h3 class="titulo-tarjeta mb-1">Productos menos vendidos (histórico)
                </h3>
                <p class="text-xs text-stone-400 mb-4 dark:text-stone-500">{{ $productosNuncaVendidos }} producto(s) nunca se han vendido (no incluidos abajo).</p>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-stone-600 dark:text-stone-300">
                        <thead class="bg-stone-50 text-stone-600 uppercase text-xs dark:bg-stone-900/60 dark:text-stone-300">
                            <tr>
                                <th class="py-2.5 px-3">Producto</th>
                                <th class="py-2.5 px-3 text-center">Unidades</th>
                                <th class="py-2.5 px-3 text-right">Ingreso</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100 dark:divide-stone-700/60">
                            @forelse($productosMenosVendidos as $p)
                                <tr class="hover:bg-stone-50/50 dark:hover:bg-stone-700/40">
                                    <td class="py-2.5 px-3 font-bold text-stone-900 dark:text-stone-100">{{ $p->producto->nombre ?? 'Eliminado' }}</td>
                                    <td class="py-2.5 px-3 text-center">{{ $p->total_vendido }}</td>
                                    <td class="py-2.5 px-3 text-right font-bold text-stone-600 dark:text-stone-300">${{ number_format($p->total_ingreso, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="py-4 text-center text-stone-500 dark:text-stone-400">Aún no hay ventas registradas.</td></tr>
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
            <div class="tarjeta">
                <h3 class="titulo-tarjeta mb-4">Clientes que más compran
                </h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-stone-600 dark:text-stone-300">
                        <thead class="bg-stone-50 text-stone-600 uppercase text-xs dark:bg-stone-900/60 dark:text-stone-300">
                            <tr>
                                <th class="py-2.5 px-3">Cliente</th>
                                <th class="py-2.5 px-3 text-center">Compras</th>
                                <th class="py-2.5 px-3 text-right">Ticket prom.</th>
                                <th class="py-2.5 px-3 text-right">Total gastado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100 dark:divide-stone-700/60">
                            @forelse($clientesFrecuentes as $cl)
                                <tr class="hover:bg-stone-50/50 dark:hover:bg-stone-700/40">
                                    <td class="py-2.5 px-3 font-bold text-stone-900 dark:text-stone-100">{{ $cl->nombre }}</td>
                                    <td class="py-2.5 px-3 text-center">{{ $cl->ventas_count }}</td>
                                    <td class="py-2.5 px-3 text-right">${{ number_format($cl->ticket_promedio, 2) }}</td>
                                    <td class="py-2.5 px-3 text-right font-bold text-green-700 dark:text-green-400">${{ number_format($cl->ventas_sum_total ?? 0, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-4 text-center text-stone-500 dark:text-stone-400">Aún no hay clientes con compras registradas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tarjeta">
                <h3 class="titulo-tarjeta mb-1">Clientes que dejaron de comprar
                </h3>
                <p class="text-xs text-stone-400 mb-4 dark:text-stone-500">Clientes activos sin compras en los últimos {{ \App\Http\Controllers\EstadisticaController::DIAS_INACTIVIDAD_CLIENTE }} días.</p>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-stone-600 dark:text-stone-300">
                        <thead class="bg-stone-50 text-stone-600 uppercase text-xs dark:bg-stone-900/60 dark:text-stone-300">
                            <tr>
                                <th class="py-2.5 px-3">Cliente</th>
                                <th class="py-2.5 px-3 text-center">Días sin comprar</th>
                                <th class="py-2.5 px-3 text-right">Saldo pendiente</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100 dark:divide-stone-700/60">
                            @forelse($clientesInactivos as $cl)
                                <tr class="hover:bg-rose-50/50 dark:hover:bg-rose-900/20">
                                    <td class="py-2.5 px-3 font-bold text-stone-900 dark:text-stone-100">{{ $cl->nombre }}</td>
                                    <td class="py-2.5 px-3 text-center">
                                        <span class="insignia-roja px-2.5 py-0.5">
                                            {{ $cl->dias_sin_comprar }} días
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-bold {{ $cl->saldo > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-stone-400 dark:text-stone-500' }}">
                                        ${{ number_format($cl->saldo, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="py-4 text-center text-stone-500 dark:text-stone-400">Todos los clientes activos han comprado recientemente.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Predicción de demanda -->
        <div class="tarjeta">
            <h3 class="titulo-tarjeta mb-1">Predicción simple de demanda — próxima semana
            </h3>
            <p class="text-xs text-stone-500 mb-4 dark:text-stone-400">Basada en el promedio móvil de ventas de las últimas 4 semanas para los 10 productos con más movimiento.</p>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-stone-600 dark:text-stone-300">
                    <thead class="text-stone-500 uppercase text-xs border-b border-stone-200 dark:border-stone-700 dark:text-stone-400">
                        <tr>
                            <th class="py-3 px-4 font-semibold">Producto</th>
                            <th class="py-3 px-4 font-semibold text-center">Vendido (4 semanas)</th>
                            <th class="py-3 px-4 font-semibold text-center">Promedio semanal</th>
                            <th class="py-3 px-4 font-semibold text-center">Proyección próx. semana</th>
                            <th class="py-3 px-4 font-semibold text-center">Stock actual</th>
                            <th class="py-3 px-4 font-semibold text-center">¿Stock suficiente?</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 dark:divide-stone-700/60">
                        @forelse($prediccion as $item)
                            <tr class="hover:bg-stone-50 dark:hover:bg-stone-700/40">
                                <td class="py-3 px-4 font-bold text-stone-900 dark:text-stone-100">{{ $item['producto']->nombre }}</td>
                                <td class="py-3 px-4 text-center">{{ $item['vendido_4_semanas'] }}</td>
                                <td class="py-3 px-4 text-center">{{ $item['promedio_semanal'] }}</td>
                                <td class="py-3 px-4 text-center font-bold text-blue-800 dark:text-blue-300">{{ $item['proyeccion_proxima_semana'] }} {{ $item['producto']->unidadLabel() }}</td>
                                <td class="py-3 px-4 text-center">{{ \App\Support\Cantidad::formato($item['producto']->stock) }}</td>
                                <td class="py-3 px-4 text-center">
                                    @if($item['cubre_con_stock'])
                                        <span class="insignia-verde">Sí alcanza</span>
                                    @else
                                        <span class="insignia-roja px-3">Reabastecer</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-stone-500 dark:text-stone-400">Aún no hay suficientes ventas registradas para proyectar demanda.</td>
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
