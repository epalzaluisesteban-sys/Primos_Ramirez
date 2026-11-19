@extends('pdf.layout')

@section('titulo', 'Lo que debemos a proveedores')
@section('subtitulo', 'Compras a crédito por pagar')
@section('resumen')
    <strong>Total por pagar:</strong> @usd($totalDeuda)
    @if($totalVencido > 0) · <strong>Vencido:</strong> @usd($totalVencido) @endif
@endsection

@section('contenido')
    <table class="datos">
        <thead>
            <tr>
                <th>Proveedor</th>
                <th>Teléfono</th>
                <th style="text-align:right;">Comprado a crédito</th>
                <th style="text-align:right;">Pagado</th>
                <th style="text-align:right;">Le debemos</th>
                <th>Próximo vencimiento</th>
            </tr>
        </thead>
        <tbody>
            @forelse($deudas as $fila)
                <tr>
                    <td>{{ $fila['proveedor']->nombre }}</td>
                    <td>{{ $fila['proveedor']->telefono ? \App\Support\Contacto::telefono($fila['proveedor']->telefono) : '—' }}</td>
                    <td style="text-align:right;">@usd($fila['comprado'])</td>
                    <td style="text-align:right;">@usd($fila['pagado'])</td>
                    <td style="text-align:right;"><strong>@usd($fila['saldo'])</strong>@if($tasa > 0)<br>@bs($fila['saldo'])@endif</td>
                    <td>
                        @if($fila['vence_el'])
                            {{ $fila['vencido'] ? 'VENCIDO desde el' : 'Vence el' }} {{ $fila['vence_el']->format('d/m/Y') }}
                        @else
                            Sin plazo
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center; padding:15px;">No se le debe nada a ningún proveedor.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
