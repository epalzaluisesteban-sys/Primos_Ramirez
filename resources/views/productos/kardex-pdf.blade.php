@extends('pdf.layout')

@section('titulo', 'Kardex - ' . $producto->nombre)
@section('subtitulo', 'Kardex — ' . $producto->nombre)
@section('resumen')
    <strong>Existencia calculada:</strong> {{ \App\Support\Cantidad::formato($existenciaFinal) }} — <strong>Stock actual del sistema:</strong> {{ \App\Support\Cantidad::formato($producto->stock) }}
@endsection

@section('contenido')
    <table class="datos">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Hora</th>
                <th style="text-align: center;">Entrada</th>
                <th style="text-align: center;">Salida</th>
                <th style="text-align: right;">Costo Ant.</th>
                <th style="text-align: right;">Costo Nuevo</th>
                <th style="text-align: center;">Existencia</th>
                <th>Usuario</th>
                <th>Observaciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($movimientos as $mov)
                <tr>
                    <td>{{ $mov['fecha']->format('d/m/Y') }}</td>
                    <td>{{ $mov['fecha']->format('h:i A') }}</td>
                    <td style="text-align: center;">{{ $mov['es_entrada'] ? '+' . \App\Support\Cantidad::formato($mov['cantidad']) : '' }}</td>
                    <td style="text-align: center;">{{ !$mov['es_entrada'] ? '-' . \App\Support\Cantidad::formato($mov['cantidad']) : '' }}</td>
                    <td style="text-align: right;">{{ $mov['costo_anterior'] !== null ? '$' . number_format($mov['costo_anterior'], 2) : 'N/D' }}</td>
                    <td style="text-align: right;">{{ $mov['costo_nuevo'] !== null ? '$' . number_format($mov['costo_nuevo'], 2) : 'N/D' }}</td>
                    <td style="text-align: center;">{{ $mov['existencia'] }}</td>
                    <td>{{ $mov['responsable'] }}</td>
                    <td>{{ $mov['observaciones'] }}</td>
                </tr>
            @empty
                <tr><td colspan="9" style="text-align:center; padding:15px;">No hay movimientos registrados.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
