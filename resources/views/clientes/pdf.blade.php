@extends('pdf.layout')

@section('titulo', 'Clientes y Cuentas por Cobrar')
@section('subtitulo', 'Clientes y Cuentas por Cobrar')
@section('resumen')
    <strong>Total de clientes:</strong> {{ $clientes->count() }}
@endsection

@section('contenido')
    <table class="datos">
        <thead>
            <tr>
                <th>Cliente</th>
                <th>RIF</th>
                <th>Teléfono</th>
                <th>Dirección</th>
                <th>Última Compra</th>
                <th style="text-align: right;">Total Comprado</th>
                <th style="text-align: right;">Saldo Pendiente</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($clientes as $c)
                <tr>
                    <td>{{ $c->nombre }}</td>
                    <td>{{ $c->rif ?: '—' }}</td>
                    <td>{{ $c->telefono ? \App\Support\Contacto::telefono($c->telefono) : '—' }}</td>
                    <td>{{ $c->direccion ?: '—' }}</td>
                    <td>{{ $c->ventas_max_fecha ? \Carbon\Carbon::parse($c->ventas_max_fecha)->format('d/m/Y') : '—' }}</td>
                    <td style="text-align: right;">${{ number_format($c->ventas_sum_total ?? 0, 2) }}</td>
                    <td style="text-align: right;">${{ number_format($c->saldo, 2) }}</td>
                    <td>{{ $c->activo ? 'Activo' : 'Inactivo' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center; padding:15px;">No hay clientes registrados.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="total-box">
        TOTAL POR COBRAR: ${{ number_format($totalPorCobrar, 2) }}
    </div>
@endsection
