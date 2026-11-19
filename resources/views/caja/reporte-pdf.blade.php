@extends('pdf.layout')

@use('App\Support\Cantidad')
@use('App\Support\Moneda')
@php $abierta = $caja->estado === 'ABIERTA'; @endphp

@section('titulo', 'Caja del ' . $caja->fechaCorta())
@section('subtitulo', 'Reporte de caja del ' . $caja->fechaCorta() . ($abierta ? ' (caja todavía abierta)' : ''))
@section('resumen')
    <strong>Tasa BCV:</strong> {{ $tasa ? 'Bs. ' . number_format($tasa, 2, ',', '.') : 'sin tasa' }}
@endsection

@section('contenido')
    <table class="info-table">
        <tr>
            <td><strong>Abrió:</strong> {{ $caja->usuario->name ?? 'Sistema' }} — {{ $caja->created_at?->format('h:i A') }}</td>
            <td style="text-align: right;">
                <strong>Cerró:</strong>
                {{ $abierta ? 'pendiente' : ($caja->cerradaPor->name ?? '—') . ($caja->cerrada_at ? ' — ' . $caja->cerrada_at->format('d/m/Y h:i A') : '') }}
            </td>
        </tr>
    </table>

    <h3 style="color:#15803d; font-size:13px; margin:0 0 6px;">Cuadre del efectivo</h3>
    <table class="datos">
        <thead>
            <tr><th></th><th style="text-align:right;">Dólares</th><th style="text-align:right;">Bolívares</th></tr>
        </thead>
        <tbody>
            <tr><td>Fondo inicial</td><td style="text-align:right;">@usd($caja->monto_apertura)</td><td style="text-align:right;">@ves($caja->monto_apertura_ves)</td></tr>
            <tr><td>Ventas de contado</td><td style="text-align:right;">@usd($resumen['contado_usd'])</td><td style="text-align:right;">@ves($resumen['contado_ves'])</td></tr>
            <tr><td>Abonos cobrados</td><td style="text-align:right;">@usd($resumen['abonos_usd'])</td><td style="text-align:right;">@ves($resumen['abonos_ves'])</td></tr>
            <tr><td>Gastos</td><td style="text-align:right;">− @usd($resumen['gastos_usd'])</td><td style="text-align:right;">− @ves($resumen['gastos_ves'])</td></tr>
            <tr><td>Pagos a proveedores (efectivo de caja)</td><td style="text-align:right;">− @usd($resumen['pagos_usd'])</td><td style="text-align:right;">− @ves($resumen['pagos_ves'])</td></tr>
            <tr><td><strong>Esperado en caja</strong></td><td style="text-align:right;"><strong>@usd($esperado['usd'])</strong></td><td style="text-align:right;"><strong>@ves($esperado['ves'])</strong></td></tr>
            @unless($abierta)
                <tr><td>Efectivo contado</td><td style="text-align:right;">@usd($caja->monto_efectivo_real)</td><td style="text-align:right;">@ves($caja->monto_efectivo_real_ves)</td></tr>
                <tr><td><strong>Diferencia</strong> (negativo = faltante)</td><td style="text-align:right;"><strong>@usd($caja->diferencia)</strong></td><td style="text-align:right;"><strong>@ves($caja->diferencia_ves)</strong></td></tr>
            @endunless
        </tbody>
    </table>
    <p style="margin:-12px 0 18px; color:#666;">Vendido en total: {{ Moneda::usd($totalVendido) }} en {{ $ventas->count() }} venta(s). Fiado del día: {{ Moneda::usd($resumen['credito']) }} (no entra como efectivo).</p>

    <h3 style="color:#15803d; font-size:13px; margin:0 0 6px;">Productos vendidos</h3>
    <table class="datos">
        <thead><tr><th>Producto</th><th style="text-align:right;">Cantidad</th><th style="text-align:right;">Total</th></tr></thead>
        <tbody>
            @forelse($productos as $p)
                <tr>
                    <td>{{ $p['producto']->nombre ?? 'Producto eliminado' }}</td>
                    <td style="text-align:right;">{{ Cantidad::formato($p['cantidad']) }} {{ $p['producto']?->unidadLabel() }}</td>
                    <td style="text-align:right;">@usd($p['total'])</td>
                </tr>
            @empty
                <tr><td colspan="3" style="text-align:center;">No se vendió nada este día.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h3 style="color:#15803d; font-size:13px; margin:0 0 6px;">Ventas</h3>
    <table class="datos">
        <thead><tr><th>N°</th><th>Hora</th><th>Cliente</th><th>Pago</th><th>Atendió</th><th style="text-align:right;">Total</th></tr></thead>
        <tbody>
            @forelse($ventas as $v)
                <tr>
                    <td>#{{ $v->id }}</td>
                    <td>{{ $v->fecha->format('h:i A') }}</td>
                    <td>{{ $v->cliente->nombre ?? '—' }}</td>
                    <td>{{ $v->tipo_pago === 'credito' ? 'Fiado' : 'Contado ' . ($v->moneda_pago === 'VES' ? 'Bs.' : '$') }}</td>
                    <td>{{ $v->usuario->name ?? 'Sistema' }}</td>
                    <td style="text-align:right;">@usd($v->total)@if($v->total_ves)<br>@ves($v->total_ves)@endif</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center;">Sin ventas.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($fiados->isNotEmpty())
        <h3 style="color:#15803d; font-size:13px; margin:0 0 6px;">Fiados del día</h3>
        <table class="datos">
            <thead><tr><th>N°</th><th>Cliente</th><th style="text-align:right;">Monto</th></tr></thead>
            <tbody>
                @foreach($fiados as $v)
                    <tr><td>#{{ $v->id }}</td><td>{{ $v->cliente->nombre ?? '—' }}</td><td style="text-align:right;">@usd($v->total)</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h3 style="color:#15803d; font-size:13px; margin:0 0 6px;">Abonos recibidos</h3>
    <table class="datos">
        <thead><tr><th>Hora</th><th>Cliente</th><th>Recibió</th><th style="text-align:right;">Monto</th></tr></thead>
        <tbody>
            @forelse($abonos as $a)
                <tr>
                    <td>{{ $a->fecha->format('h:i A') }}</td>
                    <td>{{ $a->cliente->nombre ?? '—' }}</td>
                    <td>{{ $a->usuario->name ?? 'Sistema' }}</td>
                    <td style="text-align:right;">{{ $a->moneda === 'VES' ? Moneda::ves($a->monto_moneda) : Moneda::usd($a->monto) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" style="text-align:center;">Sin abonos.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h3 style="color:#15803d; font-size:13px; margin:0 0 6px;">Gastos</h3>
    <table class="datos">
        <thead><tr><th>Hora</th><th>Concepto</th><th>Tipo</th><th>Anotó</th><th style="text-align:right;">Monto</th></tr></thead>
        <tbody>
            @forelse($gastos as $g)
                <tr>
                    <td>{{ $g->fecha->format('h:i A') }}</td>
                    <td>{{ $g->concepto }}</td>
                    <td>{{ $g->nombreCategoria() }}</td>
                    <td>{{ $g->usuario->name ?? 'Sistema' }}</td>
                    <td style="text-align:right;">{{ $g->moneda === 'VES' ? Moneda::ves($g->monto_moneda) : Moneda::usd($g->monto_moneda) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center;">Sin gastos.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($pagosProveedor->isNotEmpty())
        <h3 style="color:#15803d; font-size:13px; margin:0 0 6px;">Pagos a proveedores</h3>
        <table class="datos">
            <thead><tr><th>Hora</th><th>Proveedor</th><th>De dónde salió</th><th>Pagó</th><th style="text-align:right;">Monto</th></tr></thead>
            <tbody>
                @foreach($pagosProveedor as $p)
                    <tr>
                        <td>{{ $p->fecha->format('h:i A') }}</td>
                        <td>{{ $p->proveedor->nombre ?? '—' }}</td>
                        <td>{{ $p->salioDeCaja() ? 'Caja' : 'Otra cuenta (no cuenta en el cuadre)' }}</td>
                        <td>{{ $p->usuario->name ?? 'Sistema' }}</td>
                        <td style="text-align:right;">{{ $p->moneda === 'VES' ? Moneda::ves($p->monto_moneda) : Moneda::usd($p->monto_moneda) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($anuladas->isNotEmpty())
        <h3 style="color:#b91c1c; font-size:13px; margin:0 0 6px;">Ventas eliminadas (no cuentan en el cuadre)</h3>
        <table class="datos">
            <thead><tr><th>N°</th><th>Hora</th><th>Motivo</th><th style="text-align:right;">Total</th></tr></thead>
            <tbody>
                @foreach($anuladas as $v)
                    <tr>
                        <td>#{{ $v->id }}</td>
                        <td>{{ $v->fecha->format('h:i A') }}</td>
                        <td>{{ $v->motivo_anulacion ?: '—' }}</td>
                        <td style="text-align:right;">@usd($v->total)</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($caja->observaciones)
        <p><strong>Notas:</strong><br>{!! nl2br(e($caja->observaciones)) !!}</p>
    @endif
@endsection
