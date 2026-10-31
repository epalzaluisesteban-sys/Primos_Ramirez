<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket Venta #{{ $venta->id }}</title>
    <style>
        @page {
            size: 80mm auto;
            margin: 0;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: #000;
            width: 80mm;
            margin: 0 auto;
            padding: 8px 6px;
        }
        .center {
            text-align: center;
        }
        .negrita {
            font-weight: bold;
        }
        .linea {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        td {
            padding: 2px 0;
            vertical-align: top;
            font-size: 11px;
        }
        .col-cant {
            width: 14%;
            text-align: center;
        }
        .col-precio {
            width: 26%;
            text-align: right;
        }
        .total-row td {
            font-size: 14px;
            font-weight: bold;
            padding-top: 6px;
        }
        .footer {
            margin-top: 10px;
            font-size: 10px;
            text-align: center;
        }
        .btn-imprimir {
            display: block;
            width: 100%;
            margin: 12px 0;
            padding: 10px;
            background: #15803d;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
        }
        @media print {
            .btn-imprimir {
                display: none;
            }
            body {
                padding: 0 4px;
            }
        }
    </style>
</head>
<body>

    <button class="btn-imprimir" onclick="window.print()">🖨️ Imprimir Ticket</button>

    <div class="center negrita">DISTRIBUIDORA</div>
    <div class="center negrita">"LOS PRIMOS RAMÍREZ"</div>
    <div class="center">Frutas, Hortalizas y Víveres</div>

    <div class="linea"></div>

    <div>N° Transacción: #{{ $venta->id }}</div>
    <div>Fecha: {{ $venta->fecha->format('d/m/Y H:i') }}</div>
    <div>Atendido por: {{ $venta->usuario->name ?? 'Sistema' }}</div>
    <div>
        Pago: {{ $venta->tipo_pago === 'credito' ? 'Fiado' : 'Contado' }}
        @if($venta->tipo_pago === 'credito' && $venta->cliente)
            — {{ $venta->cliente->nombre }}
        @endif
    </div>

    <div class="linea"></div>

    <table>
        <tbody>
            @foreach($venta->detalles as $det)
                <tr>
                    <td colspan="3">{{ $det->producto->nombre ?? 'Producto eliminado' }}</td>
                </tr>
                <tr>
                    <td class="col-cant">x{{ $det->cantidad }}</td>
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

    <div class="footer">
        ¡Gracias por su compra!<br>
        Ticket generado automáticamente
    </div>

</body>
</html>
