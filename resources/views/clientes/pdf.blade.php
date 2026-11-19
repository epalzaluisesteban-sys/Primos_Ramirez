@extends('pdf.layout')

@php
    $titulo = ['todos' => 'Clientes y cuentas por cobrar', 'con_deuda' => 'Clientes que deben', 'sin_deuda' => 'Clientes sin deuda'][$estado];
    $conDeuda = $estado === 'con_deuda';
@endphp

@section('titulo', $titulo)
@section('subtitulo', $titulo)
@section('resumen')
    <strong>Clientes:</strong> {{ $clientes->count() }}
@endsection

@section('contenido')
    <table class="datos">
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Cédula / RIF</th>
                <th>Teléfono</th>
                <th>Última compra</th>
                @if($conDeuda)
                    <th>Debe desde</th>
                @else
                    <th>Dirección</th>
                @endif
                <th style="text-align: right;">Total comprado</th>
                <th style="text-align: right;">Saldo pendiente</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($clientes as $c)
                <tr>
                    <td>{{ $c->nombre }}</td>
                    <td>{{ $c->rif ?: '—' }}</td>
                    <td>{{ $c->telefono ? \App\Support\Contacto::telefono($c->telefono) : '—' }}</td>
                    <td>{{ $c->ventas_max_fecha ? \Carbon\Carbon::parse($c->ventas_max_fecha)->format('d/m/Y') : '—' }}</td>
                    @if($conDeuda)
                        <td>{{ $c->deuda_desde ? $c->deuda_desde->format('d/m/Y') . ' (' . (int) $c->deuda_desde->copy()->startOfDay()->diffInDays(today()) . ' días)' : '—' }}</td>
                    @else
                        <td>{{ $c->direccion ?: '—' }}</td>
                    @endif
                    <td style="text-align: right;">@usd($c->ventas_sum_total ?? 0)</td>
                    <td style="text-align: right;">@usd($c->saldo)</td>
                    <td>{{ $c->activo ? 'Activo' : 'Inactivo' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center; padding:15px;">No hay clientes en esta lista.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($estado !== 'sin_deuda')
        <div class="total-box">
            TOTAL POR COBRAR: @usd($clientes->sum('saldo'))
        </div>
    @endif
@endsection
