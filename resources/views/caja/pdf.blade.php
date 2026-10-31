@extends('pdf.layout')

@section('titulo', 'Historial de Caja')
@section('subtitulo', 'Historial de Apertura y Cierre de Caja')
@section('resumen')
    <strong>Total de registros:</strong> {{ $cierres->count() }}
@endsection

@section('contenido')
    <table class="datos">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Usuario</th>
                <th style="text-align: right;">Apertura ($ / Bs.)</th>
                <th style="text-align: right;">Ingresos ($ / Bs.)</th>
                <th style="text-align: right;">Efectivo Real ($ / Bs.)</th>
                <th style="text-align: right;">Diferencia ($ / Bs.)</th>
                <th>Estado</th>
                <th>Observaciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($cierres as $c)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($c->fecha)->format('d/m/Y') }}</td>
                    <td>{{ $c->usuario->name ?? 'Sistema' }}</td>
                    <td style="text-align: right;">@usd($c->monto_apertura)<br>@ves($c->monto_apertura_ves)</td>
                    <td style="text-align: right;">@usd($c->monto_ventas_sistema ?? 0)<br>@ves($c->monto_ventas_sistema_ves ?? 0)</td>
                    <td style="text-align: right;">{!! $c->monto_efectivo_real !== null ? e(\App\Support\Moneda::usd($c->monto_efectivo_real)) . '<br>' . e(\App\Support\Moneda::ves($c->monto_efectivo_real_ves)) : '—' !!}</td>
                    <td style="text-align: right;">{!! $c->diferencia !== null ? e(\App\Support\Moneda::usd($c->diferencia)) . '<br>' . e(\App\Support\Moneda::ves($c->diferencia_ves)) : '—' !!}</td>
                    <td>{{ $c->estado }}</td>
                    <td>{{ $c->observaciones ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center; padding:15px;">No hay registros de caja.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
