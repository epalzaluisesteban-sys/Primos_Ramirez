@extends('pdf.layout')

@section('titulo', 'Pérdidas de Mercancía')
@section('subtitulo', 'Pérdidas de Mercancía')
@section('resumen')
    <strong>Total de registros:</strong> {{ $mermas->count() }}
@endsection

@section('contenido')
    <table class="datos">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Producto</th>
                <th style="text-align: center;">Cantidad</th>
                <th>Motivo</th>
                <th>Observación</th>
                <th style="text-align: right;">Valor Perdido</th>
                <th>Registrado Por</th>
            </tr>
        </thead>
        <tbody>
            @forelse($mermas as $m)
                <tr>
                    <td>{{ $m->fecha->format('d/m/Y H:i') }}</td>
                    <td>{{ $m->producto->nombre ?? 'Eliminado' }}</td>
                    <td style="text-align: center;">{{ \App\Support\Cantidad::formato($m->cantidad) }}</td>
                    <td>{{ $m->motivoLabel() }}</td>
                    <td>{{ $m->observacion ?: '—' }}</td>
                    <td style="text-align: right;">${{ number_format($m->valorEstimado(), 2) }}</td>
                    <td>{{ $m->usuario->name ?? 'Sistema' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center; padding:15px;">No hay pérdidas registradas.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="total-box">
        VALOR TOTAL PERDIDO: ${{ number_format($valorTotalPerdido, 2) }}
    </div>
@endsection
