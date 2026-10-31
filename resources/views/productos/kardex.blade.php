@extends('layouts.app')

@section('title', 'Kardex - ' . $producto->nombre)

@push('head')
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <style>
        @media print {
            body.modo-etiqueta .ocultar-en-etiqueta { display: none !important; }
        }
    </style>
@endpush

@section('content')
    <div class="max-w-7xl mx-auto px-4 py-8">

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4 ocultar-en-etiqueta">
            <a href="{{ route('productos.index') }}" class="flex items-center gap-1.5 text-sm font-semibold text-stone-500 hover:text-stone-800">
                <x-icon name="arrow-left" class="w-4 h-4" /> Volver a Productos
            </a>
            <x-acciones-exportar :excel="route('productos.kardex.excel', $producto)" :pdf="route('productos.kardex.pdf', $producto)" />
        </div>

        <!-- Cabecera Informativa del Producto -->
        <div class="bg-white rounded-2xl shadow-sm border border-stone-200 p-6 mb-8 border-t-4 border-emerald-600 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 ocultar-en-etiqueta">
            <div class="flex items-center gap-4">
                @if($producto->fotoUrl())
                    <img src="{{ $producto->fotoUrl() }}" alt="{{ $producto->nombre }}" class="w-14 h-14 rounded-lg object-cover border border-stone-300">
                @endif
                <div>
                    <h2 class="font-display text-xl font-bold text-stone-900">{{ $producto->nombre }}</h2>
                    <p class="text-xs text-stone-500 font-medium mt-1">
                        Categoría: <span class="font-bold text-stone-600">{{ $producto->categoria->nombre ?? 'Sin Categoría' }}</span>
                        @if($producto->marca) · Marca: <span class="font-bold text-stone-600">{{ $producto->marca }}</span> @endif
                        @if($producto->ubicacion) · Ubicación: <span class="font-bold text-stone-600">{{ $producto->ubicacion }}</span> @endif
                    </p>
                    <p class="text-xs text-stone-500">
                        Precio de Venta: <span class="font-bold text-emerald-800">@usd($producto->precio) · @bs($producto->precio)</span>
                        @if($producto->precio_compra !== null)
                            · Precio de Compra: <span class="font-bold text-stone-600">${{ number_format($producto->precio_compra, 2) }}</span>
                            · Margen: <span class="font-bold {{ $producto->porcentajeGanancia() >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">{{ number_format($producto->porcentajeGanancia(), 1) }}%</span>
                        @endif
                    </p>
                </div>
            </div>

            <div class="bg-stone-50 border border-stone-300 px-6 py-3 rounded-xl text-center">
                <span class="text-xs text-stone-500 uppercase font-bold block">Stock Disponible Actual</span>
                <span class="text-3xl font-black {{ $producto->stock <= 5 ? 'text-rose-600' : 'text-stone-800' }}">
                    {{ $producto->stock }} <span class="text-sm font-normal text-stone-500">unids.</span>
                </span>
                @if($producto->stock_maximo)
                    <span class="text-[11px] text-stone-400 block">Máximo: {{ $producto->stock_maximo }}</span>
                @endif
            </div>
        </div>

        <!-- Etiqueta del Producto: código de barras y QR escaneables -->
        <div class="bg-white rounded-2xl shadow-sm border border-stone-200 p-6 mb-8">
            <div class="flex items-center justify-between mb-4 ocultar-en-etiqueta">
                <h3 class="font-display text-sm font-bold text-stone-800 flex items-center gap-2">
                    <x-icon name="tag" class="w-4 h-4 text-stone-500" /> Etiqueta del Producto
                </h3>
                <button type="button" id="btn-imprimir-etiqueta" class="no-print flex items-center gap-2 bg-stone-100 hover:bg-stone-200 text-stone-700 font-semibold px-4 py-2.5 rounded-xl text-sm shadow-sm transition">
                    <x-icon name="printer" class="w-4 h-4" /> Imprimir Etiqueta
                </button>
            </div>
            <div id="etiqueta-imprimible" class="flex flex-wrap items-center gap-8">
                <div>
                    <p class="text-sm font-bold text-stone-800">{{ $producto->nombre }}</p>
                    @if($producto->marca)<p class="text-xs text-stone-500">{{ $producto->marca }}</p>@endif
                    <p class="text-sm font-bold text-emerald-800 mt-1">${{ number_format($producto->precio, 2) }}</p>
                </div>
                <div class="text-center">
                    <svg id="svg-codigo-barras"></svg>
                    @if(!$producto->codigo_barras)
                        <p class="text-xs text-stone-400">Sin código de barras registrado</p>
                    @endif
                </div>
                <div class="text-center">
                    <canvas id="canvas-qr" width="120" height="120"></canvas>
                    <p class="text-[10px] text-stone-400 mt-1">{{ $producto->codigo_qr ?: 'PROD-' . $producto->id }}</p>
                </div>
            </div>
        </div>

        <!-- Tabla Kardex de Movimientos -->
        <div class="bg-white rounded-2xl shadow-sm border border-stone-200 p-6 ocultar-en-etiqueta">
            <h3 class="font-display text-base font-bold text-stone-800 mb-1 flex items-center gap-2">
                <x-icon name="clock" class="w-4 h-4 text-stone-500" /> Kardex — Entradas, Salidas y Costo
            </h3>
            @if($discrepanciaStock)
                <p class="text-xs text-amber-600 mb-4 flex items-center gap-1.5">
                    <x-icon name="alert-triangle" class="w-3.5 h-3.5" />
                    La existencia calculada a partir de los movimientos ({{ $existenciaFinal }}) difiere del stock actual del sistema ({{ $producto->stock }}); puede deberse a un ajuste manual de stock no registrado como movimiento.
                </p>
            @else
                <p class="text-xs text-stone-400 mb-4">La existencia calculada coincide con el stock actual del sistema.</p>
            @endif

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-stone-600">
                    <thead class="text-stone-500 uppercase text-xs border-b border-stone-200">
                        <tr>
                            <th class="py-3 px-4 font-semibold">Fecha</th>
                            <th class="py-3 px-4 font-semibold">Hora</th>
                            <th class="py-3 px-4 font-semibold text-center">Entrada</th>
                            <th class="py-3 px-4 font-semibold text-center">Salida</th>
                            <th class="py-3 px-4 font-semibold text-right">Costo Anterior</th>
                            <th class="py-3 px-4 font-semibold text-right">Costo Nuevo</th>
                            <th class="py-3 px-4 font-semibold text-center">Existencia</th>
                            <th class="py-3 px-4 font-semibold">Usuario</th>
                            <th class="py-3 px-4 font-semibold">Observaciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @forelse($movimientos as $mov)
                            <tr class="hover:bg-stone-50">
                                <td class="py-3 px-4 text-xs font-semibold whitespace-nowrap">{{ $mov['fecha']->format('d/m/Y') }}</td>
                                <td class="py-3 px-4 text-xs whitespace-nowrap">{{ $mov['fecha']->format('h:i A') }}</td>
                                <td class="py-3 px-4 text-center font-bold text-base text-emerald-700">
                                    {{ $mov['es_entrada'] ? '+' . $mov['cantidad'] : '—' }}
                                </td>
                                <td class="py-3 px-4 text-center font-bold text-base text-blue-700">
                                    {{ !$mov['es_entrada'] ? '-' . $mov['cantidad'] : '—' }}
                                </td>
                                <td class="py-3 px-4 text-right">
                                    {{ $mov['costo_anterior'] !== null ? '$' . number_format($mov['costo_anterior'], 2) : 'N/D' }}
                                </td>
                                <td class="py-3 px-4 text-right font-semibold">
                                    {{ $mov['costo_nuevo'] !== null ? '$' . number_format($mov['costo_nuevo'], 2) : 'N/D' }}
                                </td>
                                <td class="py-3 px-4 text-center font-bold {{ $mov['existencia'] < 0 ? 'text-rose-600' : 'text-stone-800' }}">
                                    {{ $mov['existencia'] }}
                                </td>
                                <td class="py-3 px-4 text-xs font-medium whitespace-nowrap">{{ $mov['responsable'] }}</td>
                                <td class="py-3 px-4 text-xs text-stone-500">{{ $mov['observaciones'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-8 text-center text-stone-500">
                                    No se registran movimientos de entrada ni salida para este producto aún.
                                </td>
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
        (function () {
            var codigoBarras = @json($producto->codigo_barras);
            var codigoQr = @json($producto->codigo_qr) || 'PROD-{{ $producto->id }}';

            if (codigoBarras) {
                try {
                    JsBarcode('#svg-codigo-barras', codigoBarras, { height: 50, fontSize: 12, margin: 5 });
                } catch (e) {
                    document.getElementById('svg-codigo-barras').outerHTML = '<p class="text-xs text-rose-500">Código de barras inválido</p>';
                }
            }

            QRCode.toCanvas(document.getElementById('canvas-qr'), codigoQr, { width: 120, margin: 1 });

            var btn = document.getElementById('btn-imprimir-etiqueta');
            btn.addEventListener('click', function () {
                document.body.classList.add('modo-etiqueta');
                window.print();
            });
            window.addEventListener('afterprint', function () {
                document.body.classList.remove('modo-etiqueta');
            });
        })();
    </script>
@endpush
