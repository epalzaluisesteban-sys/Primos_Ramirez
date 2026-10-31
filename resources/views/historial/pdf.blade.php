@extends('pdf.layout')

@section('titulo', 'Historial General')
@section('subtitulo', 'Historial General de Actividad')
@section('resumen')
    <strong>Período:</strong> {{ $desde }} a {{ $hasta }} — <strong>Eventos:</strong> {{ $eventos->count() }}
@endsection

@section('contenido')
    <table class="datos">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Tipo</th>
                <th>Detalle</th>
                <th>Usuario</th>
                <th style="text-align: right;">Monto</th>
            </tr>
        </thead>
        <tbody>
            @forelse($eventos as $e)
                <tr>
                    <td>{{ $e['fecha']->format('d/m/Y h:i A') }}</td>
                    <td>{{ $e['tipo'] }}</td>
                    <td>{{ $e['texto'] }}</td>
                    <td>{{ $e['usuario'] }}</td>
                    <td style="text-align: right;">{{ $e['monto'] !== null ? '$' . number_format($e['monto'], 2) : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center; padding:15px;">No hay actividad registrada en este período.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
