@extends('layouts.app')

@section('title', 'Inicio')

@php
    $esGerencia = auth()->user()->esEncargadoOSuperior();
@endphp

@push('head')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@section('content')
    <div class="pagina">

        @php
            // La app no configura APP_LOCALE=es, así que formateamos la fecha en español a mano.
            $diasSemana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
            $mesesAnio = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
            $fechaHoyTexto = $diasSemana[now()->dayOfWeek] . ' ' . now()->day . ' de ' . $mesesAnio[now()->month] . ' de ' . now()->year;
        @endphp
        <x-encabezado :titulo="'Hola, ' . \Illuminate\Support\Str::of(auth()->user()->name)->before(' ')" :subtitulo="$fechaHoyTexto" />

        @if($avisoRespaldo)
            <div class="aviso-ambar mb-6">
                <x-icon name="database" class="w-4 h-4 mt-0.5 shrink-0" />
                <p class="flex-1">{{ $avisoRespaldo }} Haz uno para no perder ventas, clientes ni inventario si algo falla.</p>
                <a href="{{ route('respaldos.index') }}" class="font-semibold underline whitespace-nowrap">Hacer respaldo</a>
            </div>
        @endif

        <!-- ============================================ -->
        <!-- ¿Qué quieres hacer? Tareas del día, según el rol y el estado de la caja -->
        <!-- ============================================ -->
        @php
            $colores = [
                'emerald' => 'bg-emerald-700 hover:bg-emerald-800 text-white border-emerald-700',
                'amber' => 'bg-amber-50 hover:bg-amber-100 text-amber-900 border-amber-300 dark:bg-amber-900/30 dark:hover:bg-amber-900/50 dark:text-amber-200 dark:border-amber-700',
                'orange' => 'bg-white hover:bg-orange-50 text-stone-800 border-stone-200 dark:bg-stone-800 dark:hover:bg-stone-700 dark:text-stone-100 dark:border-stone-700',
                'normal' => 'bg-white hover:bg-stone-50 text-stone-800 border-stone-200 dark:bg-stone-800 dark:hover:bg-stone-700 dark:text-stone-100 dark:border-stone-700',
            ];

            $cajaCerrada = $cajaHoy && $cajaHoy->estado === 'CERRADA';
            $acciones = [];

            // La caja va primero cuando falta abrirla: es lo primero que se hace en el día
            $accionCaja = match (true) {
                !$cajaHoy => ['Abrir la caja', 'Lo primero del día: anota el fondo inicial', 'banknote', route('caja.index'), 'amber'],
                $cajaCerrada => ['Caja cerrada', 'El cierre de hoy ya está hecho', 'check-circle', route('caja.index'), 'normal'],
                default => ['Cerrar la caja', 'Al final del día: cuenta el efectivo', 'lock', route('caja.index'), 'normal'],
            };
            if (!$cajaHoy) {
                $acciones[] = $accionCaja;
            }
            if (!$cajaCerrada) {
                $acciones[] = ['Nueva venta', 'Contado o fiado, en dólares o bolívares', 'cart', route('ventas.create'), 'emerald'];
            }
            $acciones[] = ['Ventas de hoy', 'Ver, imprimir o anular', 'printer', route('ventas.index', ['fecha_inicio' => now()->toDateString(), 'fecha_fin' => now()->toDateString()]), 'normal'];
            if ($cajaHoy) {
                $acciones[] = $accionCaja;
            }

            if ($esGerencia) {
                $alertas = $productosStockBajo->count() + $productosVencidos->count();
                $acciones[] = ['Cobrar un fiado', 'Registrar el abono de un cliente', 'users', route('clientes.index'), 'orange'];
                $acciones[] = ['Recibir mercancía', 'Lo que llegó del proveedor', 'inbox-down', route('compras.index'), 'normal'];
                $acciones[] = ['Registrar pérdida', 'Producto dañado o vencido', 'package-x', route('mermas.index'), 'normal'];
                $acciones[] = ['Revisar alertas', $alertas > 0 ? "{$alertas} producto(s) necesitan atención" : 'Todo el inventario está en orden', 'alert-triangle', route('productos.index', ['filtro' => 'alertas']), $alertas > 0 ? 'amber' : 'normal'];
            }
        @endphp

        <section class="mb-8" aria-labelledby="titulo-acciones">
            <h2 id="titulo-acciones" class="titulo-seccion">¿Qué quieres hacer?</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                @foreach($acciones as [$titulo, $ayuda, $icono, $url, $color])
                    <a href="{{ $url }}" class="group flex items-center gap-3 p-4 rounded-md border transition {{ $colores[$color] }}">
                        <x-icon name="{{ $icono }}" class="w-5 h-5 shrink-0 {{ $color === 'emerald' ? 'text-white' : 'text-stone-500 dark:text-stone-400' }}" />
                        <span class="min-w-0">
                            <span class="block font-display font-bold text-[15px] leading-tight">{{ $titulo }}</span>
                            <span class="block text-xs mt-0.5 {{ $color === 'emerald' ? 'text-emerald-50' : 'text-stone-500 dark:text-stone-400' }}">{{ $ayuda }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>

        <h2 class="titulo-seccion">Resumen</h2>

        <!-- ============================================ -->
        <!-- Tira de Indicadores Clave (KPIs) -->
        <!-- ============================================ -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

            <div class="tarjeta p-5">
                <p class="cifra-etiqueta">Ventas de hoy</p>
                <x-precio :usd="$ventasHoyMonto" :ves="$ventasHoyVes" class="cifra-valor" />
                <p class="text-xs text-stone-500 dark:text-stone-400 mt-1">{{ $ventasHoyCant }} venta(s) registrada(s)</p>
            </div>

            <div class="tarjeta p-5">
                <p class="cifra-etiqueta">Ventas del mes</p>
                <x-precio :usd="$ventasMesActual" :ves="$ventasMesVes" class="cifra-valor" />
                <p class="text-xs mt-1">
                    @if($variacionVentasMes === null)
                        <span class="text-stone-400 dark:text-stone-500">Sin datos del mes anterior</span>
                    @else
                        <span class="inline-flex items-center gap-1 font-bold {{ $variacionVentasMes >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                            <x-icon name="{{ $variacionVentasMes >= 0 ? 'trending-up' : 'trending-down' }}" class="w-3.5 h-3.5" />
                            {{ number_format(abs($variacionVentasMes), 1) }}%
                        </span>
                        <span class="text-stone-400 dark:text-stone-500">vs. mes anterior</span>
                    @endif
                </p>
            </div>

            @if($esGerencia)
                <div class="tarjeta p-5">
                    <p class="cifra-etiqueta">Compras del mes</p>
                    <x-precio :usd="$comprasMesActual" class="cifra-valor" />
                    <p class="text-xs mt-1">
                        @if($variacionComprasMes === null)
                            <span class="text-stone-400 dark:text-stone-500">Sin datos del mes anterior</span>
                        @else
                            <span class="inline-flex items-center gap-1 font-bold {{ $variacionComprasMes <= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                <x-icon name="{{ $variacionComprasMes >= 0 ? 'trending-up' : 'trending-down' }}" class="w-3.5 h-3.5" />
                                {{ number_format(abs($variacionComprasMes), 1) }}%
                            </span>
                            <span class="text-stone-400 dark:text-stone-500">vs. mes anterior</span>
                        @endif
                    </p>
                </div>

                <div class="tarjeta p-5">
                    <p class="cifra-etiqueta">Ganancia neta del mes</p>
                    <x-precio :usd="$gananciaNetaMes" class="mt-1 font-display text-2xl font-bold {{ $gananciaNetaMes >= 0 ? 'text-stone-800 dark:text-stone-100' : 'text-rose-600 dark:text-rose-400' }}" />
                    @if($ingresoConCostoMes > 0)
                        <p class="text-xs text-stone-400 dark:text-stone-500 mt-1">Margen: {{ number_format($margenMes, 1) }}%</p>
                    @endif
                    @if($ingresoSinCostoMes > 0)
                        <p class="text-xs text-amber-700 dark:text-amber-400 mt-1">
                            No incluye @usd($ingresoSinCostoMes) vendidos de productos sin compras registradas (costo desconocido).
                            <a href="{{ route('compras.index') }}" class="font-semibold underline">Anota sus compras</a>
                        </p>
                    @endif
                </div>
            @endif

            <div class="tarjeta p-5">
                <p class="cifra-etiqueta">Productos agotándose</p>
                <p class="cifra-valor text-amber-600 dark:text-amber-400">{{ $productosStockBajo->count() }}</p>
                <p class="text-xs text-stone-400 dark:text-stone-500 mt-1">en stock crítico</p>
            </div>

            <div class="tarjeta p-5">
                <p class="cifra-etiqueta">Productos vencidos</p>
                <p class="cifra-valor text-rose-600 dark:text-rose-400">{{ $productosVencidos->count() }}</p>
                <p class="text-xs text-stone-400 dark:text-stone-500 mt-1">{{ $productosPorVencer->count() }} por vencer (≤5 días)</p>
            </div>

            <div class="tarjeta p-5">
                <p class="cifra-etiqueta">Caja del día</p>
                <p class="font-display text-lg font-bold mt-1">
                    @if(!$cajaHoy)
                        <span class="text-rose-600 dark:text-rose-400">Sin abrir</span>
                    @elseif($cajaHoy->estado === 'ABIERTA')
                        <span class="text-emerald-600 dark:text-emerald-400">Abierta</span>
                    @else
                        <span class="text-stone-600 dark:text-stone-300">Cerrada</span>
                    @endif
                </p>
                <p class="text-xs text-stone-400 dark:text-stone-500 mt-1">
                    <a href="{{ route('caja.index') }}" class="text-emerald-700 dark:text-emerald-400 hover:underline font-semibold">Ir a la caja</a>
                </p>
            </div>

            @if($esGerencia)
                <div class="tarjeta p-5">
                    <p class="cifra-etiqueta">Valor de inventario</p>
                    <x-precio :usd="$valorInventario" class="cifra-valor" />
                    <p class="text-xs text-stone-400 dark:text-stone-500 mt-1">{{ $totalProductosInv }} productos · {{ number_format($unidadesEnStock, 0) }} unids.</p>
                </div>
            @endif
        </div>

        <!-- ============================================ -->
        <!-- Alertas: Vencidos y Por Vencer -->
        <!-- ============================================ -->
        @if($productosVencidos->count() > 0)
            <div class="aviso-rojo mb-4">
                <x-icon name="ban" class="w-5 h-5 text-rose-600 dark:text-rose-400 shrink-0 mt-0.5" />
                <div>
                    <p class="text-sm font-bold text-rose-800 dark:text-rose-300">{{ $productosVencidos->count() }} producto(s) ya vencido(s)</p>
                    <p class="text-xs text-rose-800 dark:text-rose-300 mt-1">
                        @foreach($productosVencidos as $p)
                            <span class="inline-block bg-white dark:bg-stone-800 border border-rose-300 dark:border-rose-700 rounded px-2.5 py-0.5 mr-1.5 mb-1">
                                {{ $p->nombre }} — vencido hace {{ abs($p->diasParaVencer()) }} día(s)
                            </span>
                        @endforeach
                    </p>
                </div>
            </div>
        @endif

        @if($productosPorVencer->count() > 0)
            <div class="aviso-ambar mb-8">
                <x-icon name="alert-triangle" class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
                <div>
                    <p class="text-sm font-bold text-amber-800 dark:text-amber-300">{{ $productosPorVencer->count() }} producto(s) próximos a vencer</p>
                    <p class="text-xs text-amber-800 dark:text-amber-300 mt-1">
                        @foreach($productosPorVencer as $p)
                            @php $dias = $p->diasParaVencer(); @endphp
                            <span class="inline-block bg-white dark:bg-stone-800 border border-amber-300 dark:border-amber-700 rounded px-2.5 py-0.5 mr-1.5 mb-1">
                                {{ $p->nombre }} — {{ $dias == 0 ? 'vence hoy' : 'vence en ' . $dias . ' día(s)' }}
                            </span>
                        @endforeach
                    </p>
                </div>
            </div>
        @endif

        <!-- ============================================ -->
        <!-- Gráficos principales -->
        <!-- ============================================ -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <div class="tarjeta">
                <h3 class="titulo-tarjeta mb-4">Tendencia de ventas — 14 días
                </h3>
                <div class="relative h-56">
                    <canvas id="chartVentas14Dias"></canvas>
                </div>
            </div>

            <div class="tarjeta">
                <h3 class="titulo-tarjeta mb-4">Top productos del mes
                </h3>
                <div class="relative h-56">
                    <canvas id="chartTopProductos"></canvas>
                </div>
            </div>

            <div class="tarjeta">
                <h3 class="titulo-tarjeta mb-4">Ventas del mes por tipo de pago
                </h3>
                <div class="relative h-56">
                    <canvas id="chartTipoPago"></canvas>
                </div>
            </div>
        </div>

        @if($esGerencia)
            <!-- ============================================ -->
            <!-- Comparativo Ventas vs Compras + Estado de Inventario -->
            <!-- ============================================ -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <div class="tarjeta lg:col-span-2">
                    <h3 class="titulo-tarjeta mb-4">Ventas vs. compras — últimos 6 meses
                    </h3>
                    <div class="relative h-64">
                        <canvas id="chartComparativo"></canvas>
                    </div>
                </div>

                <div class="tarjeta">
                    <h3 class="titulo-tarjeta mb-4">Estado del inventario
                    </h3>
                    <div class="relative h-40">
                        <canvas id="chartInventario"></canvas>
                    </div>
                    <div class="grid grid-cols-3 gap-2 mt-4 text-center">
                        <div>
                            <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ $productosNormales }}</p>
                            <p class="text-[10px] text-stone-500 dark:text-stone-400 uppercase">Normal</p>
                        </div>
                        <div>
                            <p class="text-lg font-bold text-amber-600 dark:text-amber-400">{{ $productosStockBajo->count() }}</p>
                            <p class="text-[10px] text-stone-500 dark:text-stone-400 uppercase">Crítico</p>
                        </div>
                        <div>
                            <p class="text-lg font-bold text-rose-600 dark:text-rose-400">{{ $productosVencidos->count() }}</p>
                            <p class="text-[10px] text-stone-500 dark:text-stone-400 uppercase">Vencido</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- ============================================ -->
        <!-- Últimas Ventas / Últimas Compras -->
        <!-- ============================================ -->
        <div class="grid grid-cols-1 {{ $esGerencia ? 'lg:grid-cols-2' : '' }} gap-6 mb-8">
            <div class="tarjeta">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="titulo-tarjeta">Últimas ventas
                    </h3>
                    <a href="{{ route('ventas.index') }}" class="text-xs text-green-700 dark:text-emerald-400 hover:underline font-bold">Ver todas →</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-stone-600 dark:text-stone-300">
                        <thead class="bg-stone-50 dark:bg-stone-900/40 text-stone-600 dark:text-stone-400 uppercase text-xs">
                            <tr>
                                <th class="py-2.5 px-3">N°</th>
                                <th class="py-2.5 px-3">Cliente</th>
                                <th class="py-2.5 px-3">Pago</th>
                                <th class="py-2.5 px-3 text-right">Monto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100 dark:divide-stone-700">
                            @forelse($ultimasVentas as $v)
                                <tr class="hover:bg-stone-50/50 dark:hover:bg-stone-700/30">
                                    <td class="py-2.5 px-3 font-bold text-stone-900 dark:text-stone-100">#{{ $v->id }}</td>
                                    <td class="py-2.5 px-3 text-xs">{{ $v->cliente->nombre ?? 'Mostrador' }}</td>
                                    <td class="py-2.5 px-3">
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded {{ $v->tipo_pago === 'credito' ? 'bg-amber-100 text-amber-800 border border-amber-300 dark:bg-amber-900/40 dark:text-amber-300 dark:border-amber-700' : 'bg-emerald-100 text-emerald-800 border border-emerald-300 dark:bg-emerald-900/40 dark:text-emerald-300 dark:border-emerald-700' }}">
                                            {{ $v->tipo_pago === 'credito' ? 'Fiado' : 'Contado' }}
                                        </span>
                                        @if($v->anulada)
                                            <span class="insignia-roja">Anulada</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-bold text-green-700 dark:text-emerald-400"><x-precio :usd="$v->total" :ves="$v->total_ves" size="small" class="items-end" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-4 text-center text-stone-500 dark:text-stone-400">No hay ventas recientes.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($esGerencia)
                <div class="tarjeta">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="titulo-tarjeta">Últimas compras
                        </h3>
                        <a href="{{ route('compras.index') }}" class="text-xs text-green-700 dark:text-emerald-400 hover:underline font-bold">Ver todas →</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-stone-600 dark:text-stone-300">
                            <thead class="bg-stone-50 dark:bg-stone-900/40 text-stone-600 dark:text-stone-400 uppercase text-xs">
                                <tr>
                                    <th class="py-2.5 px-3">Producto</th>
                                    <th class="py-2.5 px-3">Proveedor</th>
                                    <th class="py-2.5 px-3 text-center">Cant.</th>
                                    <th class="py-2.5 px-3 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100 dark:divide-stone-700">
                                @forelse($ultimasCompras as $c)
                                    <tr class="hover:bg-stone-50/50 dark:hover:bg-stone-700/30">
                                        <td class="py-2.5 px-3 font-bold text-stone-900 dark:text-stone-100">{{ $c->producto->nombre ?? 'Eliminado' }}</td>
                                        <td class="py-2.5 px-3 text-xs">{{ $c->nombreProveedor() }}</td>
                                        <td class="py-2.5 px-3 text-center">{{ \App\Support\Cantidad::formato($c->cantidad) }}</td>
                                        <td class="py-2.5 px-3 text-right font-bold text-indigo-700 dark:text-indigo-400">${{ number_format($c->total, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="py-4 text-center text-stone-500 dark:text-stone-400">No hay compras recientes.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        <!-- ============================================ -->
        <!-- Clientes Frecuentes / Top Productos -->
        <!-- ============================================ -->
        <div class="grid grid-cols-1 {{ $esGerencia ? 'lg:grid-cols-2' : '' }} gap-6 mb-8">
            @if($esGerencia)
                <div class="tarjeta">
                    <h3 class="titulo-tarjeta mb-4">Clientes frecuentes
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-stone-600 dark:text-stone-300">
                            <thead class="bg-stone-50 dark:bg-stone-900/40 text-stone-600 dark:text-stone-400 uppercase text-xs">
                                <tr>
                                    <th class="py-2.5 px-3">Cliente</th>
                                    <th class="py-2.5 px-3 text-center">Compras</th>
                                    <th class="py-2.5 px-3 text-right">Total gastado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100 dark:divide-stone-700">
                                @forelse($clientesFrecuentes as $cl)
                                    <tr class="hover:bg-stone-50/50 dark:hover:bg-stone-700/30">
                                        <td class="py-2.5 px-3 font-bold text-stone-900 dark:text-stone-100">{{ $cl->nombre }}</td>
                                        <td class="py-2.5 px-3 text-center">{{ $cl->ventas_count }}</td>
                                        <td class="py-2.5 px-3 text-right font-bold text-green-700 dark:text-emerald-400">${{ number_format($cl->ventas_sum_total ?? 0, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="py-4 text-center text-stone-500 dark:text-stone-400">Aún no hay clientes con compras registradas.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <div class="tarjeta">
                <h3 class="titulo-tarjeta mb-4">Productos más vendidos del mes
                </h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-stone-600 dark:text-stone-300">
                        <thead class="bg-stone-50 dark:bg-stone-900/40 text-stone-600 dark:text-stone-400 uppercase text-xs">
                            <tr>
                                <th class="py-2.5 px-3">Producto</th>
                                <th class="py-2.5 px-3 text-center">Unidades</th>
                                <th class="py-2.5 px-3 text-right">Ingreso</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100 dark:divide-stone-700">
                            @forelse($topProductosMes as $tp)
                                <tr class="hover:bg-stone-50/50 dark:hover:bg-stone-700/30">
                                    <td class="py-2.5 px-3 font-bold text-stone-900 dark:text-stone-100">{{ $tp->producto->nombre ?? 'Eliminado' }}</td>
                                    <td class="py-2.5 px-3 text-center">{{ $tp->total_vendido }}</td>
                                    <td class="py-2.5 px-3 text-right font-bold text-green-700 dark:text-emerald-400">${{ number_format($tp->total_ingreso, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="py-4 text-center text-stone-500 dark:text-stone-400">Aún no hay ventas este mes.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if($esGerencia)
            <!-- ============================================ -->
            <!-- Actividad Reciente -->
            <!-- ============================================ -->
            <div class="tarjeta">
                <h3 class="titulo-tarjeta mb-4">Actividad reciente
                </h3>
                @php
                    // Clases completas y literales para que Tailwind (JIT) las detecte al escanear el archivo.
                    $colorClasesActividad = [
                        'emerald' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400',
                        'blue' => 'bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-400',
                        'rose' => 'bg-rose-100 text-rose-600 dark:bg-rose-900/40 dark:text-rose-400',
                        'amber' => 'bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-400',
                        'stone' => 'bg-stone-100 text-stone-600 dark:bg-stone-700 dark:text-stone-300',
                    ];
                @endphp
                <div class="space-y-1">
                    @forelse($actividadReciente as $item)
                        <div class="flex items-center gap-3 py-2 px-2 rounded-md hover:bg-stone-50 dark:hover:bg-stone-700/40">
                            <span class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center {{ $colorClasesActividad[$item['color']] ?? 'bg-stone-100 text-stone-600 dark:bg-stone-700 dark:text-stone-300' }}">
                                <x-icon name="{{ $item['icono'] }}" class="w-4 h-4" />
                            </span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-stone-800 dark:text-stone-100 truncate">{{ $item['texto'] }}</p>
                                <p class="text-xs text-stone-400 dark:text-stone-500">{{ $item['usuario'] }} · {{ $item['fecha']?->diffForHumans() }}</p>
                            </div>
                            @if($item['monto'] !== null)
                                <span class="text-sm font-bold text-stone-700 dark:text-stone-300 shrink-0">${{ number_format($item['monto'], 2) }}</span>
                            @endif
                        </div>
                    @empty
                        <p class="text-center text-stone-500 dark:text-stone-400 py-4">No hay actividad reciente.</p>
                    @endforelse
                </div>
            </div>
        @endif

    </div>
@endsection

@push('scripts')
    <script>
        const modoOscuro = document.documentElement.classList.contains('dark');
        Chart.defaults.color = modoOscuro ? '#a8a29e' : '#78716c';
        Chart.defaults.borderColor = modoOscuro ? '#44403c' : '#e7e5e4';

        // Tendencia de ventas — 14 días
        new Chart(document.getElementById('chartVentas14Dias').getContext('2d'), {
            type: 'line',
            data: {
                labels: @json($fechasGrafico),
                datasets: [{
                    label: 'Ingresos ($)',
                    data: @json($totalesGrafico),
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

        // Top productos del mes
        new Chart(document.getElementById('chartTopProductos').getContext('2d'), {
            type: 'bar',
            data: {
                labels: @json($nombresTopProductos),
                datasets: [{
                    label: 'Unidades Vendidas',
                    data: @json($cantidadesTopProductos),
                    backgroundColor: ['#2563eb', '#16a34a', '#d97706', '#dc2626', '#9333ea', '#0891b2'],
                    borderRadius: 6
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });

        // Ventas del mes por tipo de pago
        new Chart(document.getElementById('chartTipoPago').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: @json($etiquetasTipoPago),
                datasets: [{
                    data: @json($totalesTipoPago),
                    backgroundColor: ['#16a34a', '#d97706']
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });

        @if($esGerencia)
            // Comparativo Ventas vs Compras — 6 meses
            new Chart(document.getElementById('chartComparativo').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: @json($mesesComparativo),
                    datasets: [
                        {
                            label: 'Ventas ($)',
                            data: @json($serieVentasComparativo),
                            backgroundColor: '#16a34a',
                            borderRadius: 6
                        },
                        {
                            label: 'Compras ($)',
                            data: @json($serieComprasComparativo),
                            backgroundColor: '#4f46e5',
                            borderRadius: 6
                        }
                    ]
                },
                options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
            });

            // Estado del inventario
            new Chart(document.getElementById('chartInventario').getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Normal', 'Crítico', 'Vencido'],
                    datasets: [{
                        data: [{{ $productosNormales }}, {{ $productosStockBajo->count() }}, {{ $productosVencidos->count() }}],
                        backgroundColor: ['#16a34a', '#d97706', '#dc2626']
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } } }
            });
        @endif
    </script>
@endpush
