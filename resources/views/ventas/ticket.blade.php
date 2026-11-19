@extends('pdf.ticket')

@section('titulo', 'Ticket venta #' . $venta->id)

@section('contenido')
    <div class="center">Frutas, Hortalizas y Víveres</div>

    <div class="linea"></div>

    <div>N° Transacción: #{{ $venta->id }}</div>
    <div>Fecha: {{ $venta->fecha->format('d/m/Y h:i A') }}</div>
    <div>Atendido por: {{ $venta->usuario->name ?? 'Sistema' }}</div>
    <div>
        Pago: {{ $venta->tipo_pago === 'credito' ? 'Fiado' : 'Contado' }}
        @if($venta->cliente) — {{ $venta->cliente->nombre }} @endif
    </div>

    <div class="linea"></div>

    <table>
        <tbody>
            @foreach($venta->detalles as $det)
                <tr>
                    <td colspan="3">{{ $det->producto->nombre ?? 'Producto eliminado' }}</td>
                </tr>
                <tr>
                    <td class="col-cant">x{{ \App\Support\Cantidad::formato($det->cantidad) }}</td>
                    <td class="col-precio">${{ number_format($det->precio_unitario, 2) }}</td>
                    <td class="col-precio">${{ number_format($det->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="linea"></div>

    <table>
        <tr class="total-row">
            <td>TOTAL</td>
            <td colspan="2" style="text-align: right;">${{ number_format($venta->total, 2) }}@if($venta->total_ves)<br><span style="font-size:0.85em;">Bs. {{ number_format($venta->total_ves, 2, ',', '.') }}</span>@endif</td>
        </tr>
    </table>

    <div class="linea"></div>

    <div class="footer">¡Gracias por su compra!</div>
@endsection
