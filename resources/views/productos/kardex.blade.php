@extends('layouts.app')

@section('title', 'Kardex - ' . $producto->nombre)

@push('head')
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    {{-- qrcode 1.5.x ya no publica build/qrcode.min.js (404); 1.4.4 sí, con la misma API toCanvas --}}
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.4.4/build/qrcode.min.js"></script>
    <style>
        @media print {
            body.modo-etiqueta .ocultar-en-etiqueta { display: none !important; }
        }
    </style>
@endpush

@section('content')
    <div class="pagina">

        <x-encabezado titulo="Kardex" subtitulo="Cada entrada y salida de este producto, con la existencia que quedó." :volver="route('productos.index')" class="ocultar-en-etiqueta">
            <x-acciones-exportar :excel="route('productos.kardex.excel', $producto)" :pdf="route('productos.kardex.pdf', $producto)" />
        </x-encabezado>

        <!-- Cabecera del producto -->
        <div class="tarjeta mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 ocultar-en-etiqueta">
            <div class="flex items-center gap-4">
                @if($producto->fotoUrl())
                    <img src="{{ $producto->fotoUrl() }}" alt="{{ $producto->nombre }}" class="w-14 h-14 rounded-md object-cover border border-stone-300 dark:border-stone-700">
                @endif
                <div>
                    <h2 class="titulo-tarjeta text-lg">{{ $producto->nombre }}</h2>
                    <p class="text-xs text-stone-500 dark:text-stone-400 mt-1">
                        Categoría: <span class="font-bold text-stone-700 dark:text-stone-200">{{ $producto->categoria->nombre ?? 'Sin categoría' }}</span>
                        @if($producto->marca) · Marca: <span class="font-bold text-stone-700 dark:text-stone-200">{{ $producto->marca }}</span> @endif
                        @if($producto->ubicacion) · Ubicación: <span class="font-bold text-stone-700 dark:text-stone-200">{{ $producto->ubicacion }}</span> @endif
                    </p>
                    <p class="text-xs text-stone-500 dark:text-stone-400 mt-0.5">
                        Precio de venta: <span class="font-bold text-emerald-800 dark:text-emerald-400">@usd($producto->precio) · @bs($producto->precio)</span>
                        @if($producto->precio_compra !== null)
                            · Costo: <span class="font-bold text-stone-700 dark:text-stone-200">@usd($producto->precio_compra)</span>
                            · Margen: <span class="font-bold {{ $producto->porcentajeGanancia() >= 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">{{ number_format($producto->porcentajeGanancia(), 1) }}%</span>
                        @endif
                    </p>
                </div>
            </div>

            <div class="caja-suave px-6 py-3 text-center shrink-0">
                <span class="etiqueta mb-0">Existencia actual</span>
                <span class="font-display text-3xl font-black {{ $producto->tieneStockCritico() ? 'text-rose-600 dark:text-rose-400' : 'text-stone-800 dark:text-stone-100' }}">
                    {{ \App\Support\Cantidad::formato($producto->stock) }} <span class="text-sm font-normal text-stone-500 dark:text-stone-400">{{ $producto->unidadLabel() }}</span>
                </span>
                <span class="text-[11px] text-stone-400 dark:text-stone-500 block">
                    Mínimo: {{ \App\Support\Cantidad::formato($producto->stock_minimo) }}@if($producto->stock_maximo) · Máximo: {{ \App\Support\Cantidad::formato($producto->stock_maximo) }}@endif
                </span>
            </div>
        </div>

        <!-- Etiqueta del producto: código de barras y QR escaneables -->
        <div class="tarjeta mb-6">
            <div class="flex items-center justify-between gap-3 mb-4 ocultar-en-etiqueta">
                <h3 class="titulo-tarjeta">Etiqueta del producto
                </h3>
                <button type="button" id="btn-imprimir-etiqueta" class="btn-secundario btn-sm no-print">
                    <x-icon name="printer" class="w-4 h-4" /> Imprimir etiqueta
                </button>
            </div>
            {{-- Siempre en blanco, también en modo oscuro: es la vista previa del papel y los lectores necesitan fondo claro --}}
            <div id="etiqueta-imprimible" class="inline-flex flex-wrap items-center gap-x-8 gap-y-4 max-w-full bg-white text-stone-800 rounded-md border border-stone-200 dark:border-stone-600 p-4 [&_svg]:max-w-full [&_canvas]:max-w-full">
                <div>
                    <p class="text-sm font-bold">{{ $producto->nombre }}</p>
                    @if($producto->marca)<p class="text-xs text-stone-500">{{ $producto->marca }}</p>@endif
                    <p class="text-sm font-bold text-emerald-800 mt-1">@usd($producto->precio)</p>
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

        <!-- Kardex de movimientos -->
        <div class="tarjeta p-0 overflow-hidden ocultar-en-etiqueta">
            <div class="px-6 py-4 border-b border-stone-200 dark:border-stone-700">
                <h3 class="titulo-tarjeta">Movimientos (kardex)
                </h3>
                @if($discrepanciaStock)
                    <div class="aviso-ambar mt-3 text-xs">
                        <x-icon name="alert-triangle" class="w-4 h-4 shrink-0" />
                        <span>La existencia según los movimientos ({{ \App\Support\Cantidad::formato($existenciaFinal) }}) no coincide con el stock del sistema ({{ \App\Support\Cantidad::formato($producto->stock) }}). El stock se cambió por fuera del sistema; corrígelo con un ajuste de inventario.</span>
                    </div>
                @else
                    <p class="text-xs text-stone-500 dark:text-stone-400 mt-1 flex items-center gap-1.5">
                        <x-icon name="check-circle" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" /> Los movimientos cuadran con el stock actual.
                    </p>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th class="text-center">Movimiento</th>
                            <th class="text-right">Costo</th>
                            <th class="text-center">Existencia</th>
                            <th>Usuario</th>
                            <th>Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movimientos as $mov)
                            <tr>
                                <td class="whitespace-nowrap">
                                    <span class="block text-xs font-semibold text-stone-700 dark:text-stone-200">{{ $mov['fecha']->format('d/m/Y') }}</span>
                                    <span class="block text-[11px] text-stone-400 dark:text-stone-500">{{ $mov['fecha']->format('h:i A') }}</span>
                                </td>
                                <td class="text-center whitespace-nowrap">
                                    @if($mov['es_entrada'])
                                        <span class="font-bold text-base text-emerald-700 dark:text-emerald-400">+{{ \App\Support\Cantidad::formato($mov['cantidad']) }}</span>
                                        <span class="block text-[10px] uppercase font-semibold text-stone-400 dark:text-stone-500">Entrada</span>
                                    @else
                                        <span class="font-bold text-base text-rose-600 dark:text-rose-400">−{{ \App\Support\Cantidad::formato($mov['cantidad']) }}</span>
                                        <span class="block text-[10px] uppercase font-semibold text-stone-400 dark:text-stone-500">Salida</span>
                                    @endif
                                </td>
                                <td class="text-right whitespace-nowrap">
                                    @php
                                        $antes = $mov['costo_anterior'];
                                        $despues = $mov['costo_nuevo'];
                                    @endphp
                                    @if($despues === null)
                                        <span class="text-stone-400 dark:text-stone-500">N/D</span>
                                    @elseif($antes !== null && round($antes, 2) != round($despues, 2))
                                        <span class="text-xs text-stone-400 dark:text-stone-500 line-through">@usd($antes)</span>
                                        <span class="font-semibold text-stone-800 dark:text-stone-100">@usd($despues)</span>
                                    @else
                                        <span class="font-semibold text-stone-800 dark:text-stone-100">@usd($despues)</span>
                                    @endif
                                </td>
                                <td class="text-center font-bold {{ $mov['existencia'] < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-stone-800 dark:text-stone-100' }}">
                                    {{ $mov['existencia'] }}
                                </td>
                                <td class="text-xs font-medium whitespace-nowrap">{{ $mov['responsable'] }}</td>
                                <td class="text-xs text-stone-500 dark:text-stone-400">{{ $mov['observaciones'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="tabla-vacia">Este producto todavía no tiene entradas ni salidas.</td>
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
