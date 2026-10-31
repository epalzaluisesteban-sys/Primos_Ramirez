@extends('layouts.app')

@section('title', 'Inventario Inteligente')

@section('content')
    <div class="max-w-7xl mx-auto px-4 py-8">

        <div class="mb-6">
            <h2 class="font-display text-xl font-bold text-stone-900 flex items-center gap-2">
                <x-icon name="alert-triangle" class="w-5 h-5 text-rose-600" /> Inventario Inteligente
            </h2>
            <p class="text-sm text-stone-500 mt-0.5">Alertas automáticas de stock, vencimiento y sugerencias de compra.</p>
        </div>

        <!-- ============================================ -->
        <!-- KPIs -->
        <!-- ============================================ -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
            <div class="bg-white rounded-2xl shadow-sm border border-stone-200 p-5 border-t-4 border-rose-600">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold text-stone-500 uppercase tracking-wide">Agotados</p>
                    <x-icon name="ban" class="w-4 h-4 text-rose-600" />
                </div>
                <p class="font-display text-2xl font-bold text-rose-600 mt-1">{{ $agotados->count() }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-stone-200 p-5 border-t-4 border-rose-500">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold text-stone-500 uppercase tracking-wide">Stock Crítico</p>
                    <x-icon name="alert-triangle" class="w-4 h-4 text-rose-500" />
                </div>
                <p class="font-display text-2xl font-bold text-rose-600 mt-1">{{ $criticos->count() }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-stone-200 p-5 border-t-4 border-amber-500">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold text-stone-500 uppercase tracking-wide">Vencidos / Por Vencer</p>
                    <x-icon name="clock" class="w-4 h-4 text-amber-600" />
                </div>
                <p class="font-display text-2xl font-bold text-amber-600 mt-1">{{ $vencidos->count() + $porVencer->count() }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-stone-200 p-5 border-t-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold text-stone-500 uppercase tracking-wide">Sugerencias de Compra</p>
                    <x-icon name="inbox-down" class="w-4 h-4 text-blue-600" />
                </div>
                <p class="font-display text-2xl font-bold text-blue-600 mt-1">{{ $sugerenciasCompra->count() }}</p>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- Feed de alertas, agrupado por severidad -->
        <!-- ============================================ -->
        <div class="space-y-8">

            <!-- Agotados -->
            <div class="bg-white rounded-2xl shadow-sm border border-stone-200 p-6">
                <h3 class="font-display text-sm font-bold text-stone-800 mb-4 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-600"></span> Productos Agotados
                </h3>
                <div class="space-y-2">
                    @forelse($agotados as $p)
                        <a href="{{ route('productos.edit', $p) }}" class="flex items-center gap-3 p-3 rounded-xl bg-rose-50 border border-rose-200 hover:bg-rose-100 transition">
                            <x-icon name="ban" class="w-4 h-4 text-rose-600 shrink-0" />
                            <span class="text-sm font-semibold text-rose-800">Producto agotado: {{ $p->nombre }}</span>
                            <span class="ml-auto text-xs text-rose-600">{{ $p->categoria->nombre ?? '' }}</span>
                        </a>
                    @empty
                        <p class="text-sm text-stone-500 flex items-center gap-1.5"><x-icon name="check-circle" class="w-4 h-4 text-emerald-500" /> No hay productos agotados.</p>
                    @endforelse
                </div>
            </div>

            <!-- Stock crítico -->
            <div class="bg-white rounded-2xl shadow-sm border border-stone-200 p-6">
                <h3 class="font-display text-sm font-bold text-stone-800 mb-4 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> Stock Crítico
                </h3>
                <div class="space-y-2">
                    @forelse($criticos as $p)
                        <a href="{{ route('productos.edit', $p) }}" class="flex items-center gap-3 p-3 rounded-xl bg-rose-50 border border-rose-200 hover:bg-rose-100 transition">
                            <x-icon name="alert-triangle" class="w-4 h-4 text-rose-500 shrink-0" />
                            <span class="text-sm font-semibold text-rose-800">Quedan {{ $p->stock }} {{ $p->unidadLabel() }} de {{ $p->nombre }}</span>
                            <span class="ml-auto text-xs text-rose-600">Mínimo: {{ $p->stock_minimo }}</span>
                        </a>
                    @empty
                        <p class="text-sm text-stone-500 flex items-center gap-1.5"><x-icon name="check-circle" class="w-4 h-4 text-emerald-500" /> No hay productos en stock crítico.</p>
                    @endforelse
                </div>
            </div>

            <!-- Vencidos y por vencer -->
            <div class="bg-white rounded-2xl shadow-sm border border-stone-200 p-6">
                <h3 class="font-display text-sm font-bold text-stone-800 mb-4 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Vencidos y Próximos a Vencer
                </h3>
                <div class="space-y-2">
                    @forelse($vencidos->concat($porVencer) as $p)
                        @php $dias = $p->diasParaVencer(); @endphp
                        <a href="{{ route('productos.edit', $p) }}" class="flex items-center gap-3 p-3 rounded-xl bg-amber-50 border border-amber-200 hover:bg-amber-100 transition">
                            <x-icon name="clock" class="w-4 h-4 text-amber-600 shrink-0" />
                            <span class="text-sm font-semibold text-amber-800">
                                @if($dias < 0)
                                    {{ $p->nombre }} vencido hace {{ abs($dias) }} día(s)
                                @elseif($dias == 0)
                                    {{ $p->nombre }} vence hoy
                                @else
                                    {{ $p->nombre }} vence en {{ $dias }} día(s)
                                @endif
                            </span>
                            <span class="ml-auto text-xs text-amber-600">{{ $p->categoria->nombre ?? '' }}</span>
                        </a>
                    @empty
                        <p class="text-sm text-stone-500 flex items-center gap-1.5"><x-icon name="check-circle" class="w-4 h-4 text-emerald-500" /> No hay productos vencidos ni próximos a vencer.</p>
                    @endforelse
                </div>
            </div>

            <!-- Sugerencias de compra -->
            <div class="bg-white rounded-2xl shadow-sm border border-stone-200 p-6">
                <h3 class="font-display text-sm font-bold text-stone-800 mb-1 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> Se Necesita Realizar Compra
                </h3>
                <p class="text-xs text-stone-400 mb-4">Productos agotados/críticos, o cuya demanda proyectada de la próxima semana supera el stock actual.</p>
                <div class="space-y-2">
                    @forelse($sugerenciasCompra as $p)
                        <a href="{{ route('compras.index') }}" class="flex items-center gap-3 p-3 rounded-xl bg-blue-50 border border-blue-200 hover:bg-blue-100 transition">
                            <x-icon name="inbox-down" class="w-4 h-4 text-blue-600 shrink-0" />
                            <span class="text-sm font-semibold text-blue-800">Se necesita comprar: {{ $p->nombre }}</span>
                            <span class="ml-auto text-xs text-blue-600">Stock actual: {{ $p->stock }} {{ $p->unidadLabel() }}</span>
                        </a>
                    @empty
                        <p class="text-sm text-stone-500 flex items-center gap-1.5"><x-icon name="check-circle" class="w-4 h-4 text-emerald-500" /> No hay sugerencias de compra por ahora.</p>
                    @endforelse
                </div>
            </div>

        </div>

    </div>
@endsection
