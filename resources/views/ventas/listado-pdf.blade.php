@extends('pdf.layout')

@php
    $periodo = $desde || $hasta
        ? 'del ' . ($desde ? \Carbon\Carbon::parse($desde)->format('d/m/Y') : 'inicio') . ' al ' . ($hasta ? \Carbon\Carbon::parse($hasta)->format('d/m/Y') : 'hoy')
        : 'todas las fechas';
@endphp

@section('titulo', 'Ventas')
@section('subtitulo', 'Ventas registradas, ' . $periodo . ($categoria ? ' — categoría ' . $categoria->nombre : ''))
@section('resumen')
    <strong>Ventas:</strong> {{ $ventas->count() }} · <strong>Total:</strong> @usd($totalPeriodo)
    @if($categoria) · <strong>Vendido en {{ $categoria->nombre }}:</strong> @usd($totalCategoria) @endif
@endsection

@section('contenido')
    <table class="datos">
        <thead>
            <tr>
                <th>N°</th>
                <th>Fecha</th>
                <th>Cliente</th>
                <th>Pago</th>
                <th>Productos</th>
                <th>Atendió</th>
                <th style="text-align:right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ventas as $v)
                <tr>
                    <td>#{{ $v->id }}</td>
                    <td>{{ $v->fecha->format('d/m/Y h:i A') }}</td>
                    <td>{{ $v->cliente->nombre ?? '—' }}</td>
                    <td>{{ $v->tipo_pago === 'credito' ? 'Fiado' : 'Contado ' . ($v->moneda_pago === 'VES' ? 'Bs.' : '$') }}</td>
                    <td>
                        {{ $v->detalles->map(fn ($d) => ($d->producto->nombre ?? 'Producto eliminado') . ' x' . \App\Support\Cantidad::formato($d->cantidad))->join(', ') }}
                    </td>
                    <td>{{ $v->usuario->name ?? 'Sistema' }}</td>
                    <td style="text-align:right;">@usd($v->total)@if($v->total_ves)<br>@ves($v->total_ves)@endif</td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center; padding:15px;">No hay ventas con estos filtros.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
