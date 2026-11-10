@extends('pdf.layout')

@section('titulo', 'Reporte de Ganancias')
@section('subtitulo', 'Reporte de Ganancias' . ($desde || $hasta ? ' (' . ($desde ?: 'inicio') . ' a ' . ($hasta ?: 'hoy') . ')' : ''))
@section('resumen')
    <strong>Utilidad Total:</strong> ${{ number_format($utilidadTotal, 2) }} ({{ number_format($margenTotal, 1) }}%)
@endsection

@section('contenido')
    <table class="datos">
        <thead>
            <tr>
                <th>Producto</th>
                <th style="text-align: center;">Unidades</th>
                <th style="text-align: right;">Ingreso</th>
                <th style="text-align: right;">Costo</th>
                <th style="text-align: right;">Utilidad</th>
                <th style="text-align: right;">Margen</th>
            </tr>
        </thead>
        <tbody>
            @forelse($porProducto as $nombre => $p)
                <tr>
                    <td>{{ $nombre }}</td>
                    <td style="text-align: center;">{{ \App\Support\Cantidad::formato($p['cantidad']) }}</td>
                    <td style="text-align: right;">${{ number_format($p['ingreso'], 2) }}</td>
                    <td style="text-align: right;">{{ $p['costo_conocido'] ? '$' . number_format($p['costo'], 2) : 'N/D' }}</td>
                    <td style="text-align: right;">@if($p['costo_conocido'])${{ number_format($p['utilidad'], 2) }}@else N/D @endif</td>
                    <td style="text-align: right;">{{ $p['costo_conocido'] ? number_format($p['margen'], 1) . '%' : 'N/D' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center; padding:15px;">No hay ventas en este período.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="datos">
        <thead>
            <tr>
                <th>Categoría</th>
                <th style="text-align: center;">Unidades</th>
                <th style="text-align: right;">Ingreso</th>
                <th style="text-align: right;">Costo</th>
                <th style="text-align: right;">Utilidad</th>
                <th style="text-align: right;">Margen</th>
            </tr>
        </thead>
        <tbody>
            @forelse($porCategoria as $nombre => $c)
                <tr>
                    <td>{{ $nombre }}</td>
                    <td style="text-align: center;">{{ \App\Support\Cantidad::formato($c['cantidad']) }}</td>
                    <td style="text-align: right;">${{ number_format($c['ingreso'], 2) }}</td>
                    <td style="text-align: right;">{{ $c['costo_conocido'] ? '$' . number_format($c['costo'], 2) : 'N/D' }}</td>
                    <td style="text-align: right;">@if($c['costo_conocido'])${{ number_format($c['utilidad'], 2) }}@else N/D @endif</td>
                    <td style="text-align: right;">{{ $c['costo_conocido'] ? number_format($c['margen'], 1) . '%' : 'N/D' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center; padding:15px;">No hay ventas en este período.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="total-box">
        INGRESOS: ${{ number_format($ingresoTotal, 2) }} — COSTO: ${{ number_format($costoTotal, 2) }} — UTILIDAD: ${{ number_format($utilidadTotal, 2) }} ({{ number_format($margenTotal, 1) }}%)
    </div>
@endsection
