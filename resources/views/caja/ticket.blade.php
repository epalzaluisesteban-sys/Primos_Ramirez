@extends('pdf.ticket')

@section('titulo', 'Cierre de caja ' . $caja->fechaCorta())

@section('contenido')
    @php
        $usd = fn ($m) => \App\Support\Moneda::usd($m);
        $ves = fn ($m) => \App\Support\Moneda::ves($m);
        $abierta = $caja->estado === 'ABIERTA';
    @endphp
    <div class="center">{{ $abierta ? 'Corte de caja (abierta)' : 'Cierre de caja' }}</div>

    <div class="linea"></div>

    <div>Fecha: {{ $caja->fechaCorta() }}</div>
    <div>Abrió: {{ $caja->usuario->name ?? 'Sistema' }} {{ $caja->created_at?->format('h:i A') }}</div>
    @unless($abierta)
        <div>Cerró: {{ $caja->cerradaPor->name ?? '—' }} {{ $caja->cerrada_at?->format('h:i A') }}</div>
    @endunless
    <div>Ventas: {{ $ventas->count() }} — Total {{ $usd($totalVendido) }}</div>

    <div class="linea"></div>
    <div class="negrita">VENDIDO POR PRODUCTO</div>
    <table>
        <tbody>
            @forelse($productos as $p)
                <tr><td colspan="2">{{ $p['producto']->nombre ?? 'Producto eliminado' }}</td></tr>
                <tr>
                    <td>x{{ \App\Support\Cantidad::formato($p['cantidad']) }} {{ $p['producto']?->unidadLabel() }}</td>
                    <td class="col-precio">{{ $usd($p['total']) }}</td>
                </tr>
            @empty
                <tr><td colspan="2">Sin ventas.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="linea"></div>
    <div class="negrita">EFECTIVO</div>
    <table>
        <tbody>
            <tr><td></td><td class="col-precio">$</td><td class="col-precio">Bs.</td></tr>
            <tr><td>Fondo</td><td class="col-precio">{{ number_format($caja->monto_apertura, 2) }}</td><td class="col-precio">{{ number_format($caja->monto_apertura_ves, 2, ',', '.') }}</td></tr>
            <tr><td>Contado</td><td class="col-precio">{{ number_format($resumen['contado_usd'], 2) }}</td><td class="col-precio">{{ number_format($resumen['contado_ves'], 2, ',', '.') }}</td></tr>
            <tr><td>Abonos</td><td class="col-precio">{{ number_format($resumen['abonos_usd'], 2) }}</td><td class="col-precio">{{ number_format($resumen['abonos_ves'], 2, ',', '.') }}</td></tr>
            <tr><td>Gastos</td><td class="col-precio">-{{ number_format($resumen['gastos_usd'], 2) }}</td><td class="col-precio">-{{ number_format($resumen['gastos_ves'], 2, ',', '.') }}</td></tr>
            @if($resumen['pagos_usd'] > 0 || $resumen['pagos_ves'] > 0)
                <tr><td>Pagos prov.</td><td class="col-precio">-{{ number_format($resumen['pagos_usd'], 2) }}</td><td class="col-precio">-{{ number_format($resumen['pagos_ves'], 2, ',', '.') }}</td></tr>
            @endif
            <tr class="negrita"><td>Esperado</td><td class="col-precio">{{ number_format($esperado['usd'], 2) }}</td><td class="col-precio">{{ number_format($esperado['ves'], 2, ',', '.') }}</td></tr>
            @unless($abierta)
                <tr><td>Contado real</td><td class="col-precio">{{ number_format($caja->monto_efectivo_real, 2) }}</td><td class="col-precio">{{ number_format($caja->monto_efectivo_real_ves, 2, ',', '.') }}</td></tr>
                <tr class="negrita"><td>Diferencia</td><td class="col-precio">{{ number_format($caja->diferencia, 2) }}</td><td class="col-precio">{{ number_format($caja->diferencia_ves, 2, ',', '.') }}</td></tr>
            @endunless
        </tbody>
    </table>
    <div>Fiado del día: {{ $usd($resumen['credito']) }}</div>

    @if($gastos->isNotEmpty())
        <div class="linea"></div>
        <div class="negrita">GASTOS</div>
        <table>
            <tbody>
                @foreach($gastos as $g)
                    <tr>
                        <td>{{ $g->concepto }}</td>
                        <td class="col-precio">{{ $g->moneda === 'VES' ? $ves($g->monto_moneda) : $usd($g->monto_moneda) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($anuladas->isNotEmpty())
        <div class="linea"></div>
        <div>Ventas eliminadas: {{ $anuladas->count() }} (no cuentan)</div>
    @endif

    <div class="linea"></div>
    <div class="footer">Impreso el {{ now()->format('d/m/Y h:i A') }}</div>
@endsection
