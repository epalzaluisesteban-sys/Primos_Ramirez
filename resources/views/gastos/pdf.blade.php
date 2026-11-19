@extends('pdf.layout')

@section('titulo', 'Gastos')
@section('subtitulo', 'Gastos del ' . $desde->format('d/m/Y') . ' al ' . $hasta->format('d/m/Y') . ($tipo ? ' — ' . \App\Models\Gasto::CATEGORIAS[$tipo] : ''))
@section('resumen')
    <strong>Gastos:</strong> {{ $gastos->count() }}
@endsection

@section('contenido')
    <table class="datos">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Concepto</th>
                <th>Tipo</th>
                <th style="text-align: right;">Monto</th>
                <th style="text-align: right;">Equivalente $</th>
                <th>Anotado por</th>
            </tr>
        </thead>
        <tbody>
            @forelse($gastos as $g)
                <tr>
                    <td>{{ $g->fecha->format('d/m/Y h:i A') }}</td>
                    <td>{{ $g->concepto }}</td>
                    <td>{{ $g->nombreCategoria() }}</td>
                    <td style="text-align: right;">{{ $g->moneda === 'VES' ? \App\Support\Moneda::ves($g->monto_moneda) : \App\Support\Moneda::usd($g->monto_moneda) }}</td>
                    <td style="text-align: right;">{{ \App\Support\Moneda::usd($g->monto) }}</td>
                    <td>{{ $g->usuario->name ?? 'Sistema' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center; padding:15px;">No hay gastos en estas fechas.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($porTipo->isNotEmpty())
        <table class="datos" style="margin-top: 15px; width: 50%;">
            <thead>
                <tr><th>Tipo de gasto</th><th style="text-align: center;">Cantidad</th><th style="text-align: right;">Total $</th></tr>
            </thead>
            <tbody>
                @foreach($porTipo as $fila)
                    <tr>
                        <td>{{ $fila['nombre'] }}</td>
                        <td style="text-align: center;">{{ $fila['cantidad'] }}</td>
                        <td style="text-align: right;">{{ \App\Support\Moneda::usd($fila['total']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="total-box">
        PAGADO EN $: {{ \App\Support\Moneda::usd($totalUsd) }} · PAGADO EN BS.: {{ \App\Support\Moneda::ves($totalVes) }} · TOTAL EQUIVALENTE: {{ \App\Support\Moneda::usd($totalEquivalente) }}
    </div>
@endsection
