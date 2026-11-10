<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Venta #{{ $venta->id }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #15803d;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #15803d;
            margin: 0;
            font-size: 20px;
        }
        .header p {
            margin: 3px 0;
            color: #666;
            font-size: 11px;
        }
        .info-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .info-table td {
            padding: 4px 0;
        }
        .tabla-productos {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .tabla-productos th {
            background-color: #15803d;
            color: #ffffff;
            text-align: left;
            padding: 8px;
            font-size: 11px;
        }
        .tabla-productos td {
            border-bottom: 1px solid #ddd;
            padding: 8px;
        }
        .total-box {
            text-align: right;
            font-size: 14px;
            font-weight: bold;
            color: #15803d;
            margin-top: 10px;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #888;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Distribuidora "Los Primos Ramírez"</h1>
        <p>Venta e Intermediación de Frutas, Hortalizas y Víveres en General</p>
        <p>Comprobante Digital de Venta</p>
    </div>

    <table class="info-table">
        <tr>
            <td><strong>N° Transacción:</strong> #{{ $venta->id }}</td>
            <td style="text-align: right;"><strong>Fecha:</strong> {{ $venta->fecha }}</td>
        </tr>
        <tr>
            <td><strong>Atendido Por:</strong> {{ $venta->usuario->name ?? 'Sistema' }}</td>
            <td style="text-align: right;"><strong>Estatus:</strong> Procesado / Pagado</td>
        </tr>
    </table>

    <table class="tabla-productos">
        <thead>
            <tr>
                <th>Producto</th>
                <th style="text-align: center;">Cantidad</th>
                <th style="text-align: right;">Precio Unit.</th>
                <th style="text-align: right;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($venta->detalles as $detalle)
                <tr>
                    <td>{{ $detalle->producto->nombre ?? 'Producto no disponible' }}</td>
                    <td style="text-align: center;">{{ \App\Support\Cantidad::formato($detalle->cantidad) }}</td>
                    <td style="text-align: right;">${{ number_format($detalle->precio_unitario, 2) }}</td>
                    <td style="text-align: right;">${{ number_format($detalle->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-box">
        TOTAL PAGADO: ${{ number_format($venta->total, 2) }}@if($venta->total_ves) &nbsp;|&nbsp; Bs. {{ number_format($venta->total_ves, 2, ',', '.') }} (tasa BCV {{ number_format($venta->tasa_cambio, 4, ',', '.') }}) @endif
    </div>

    <div class="footer">
        <p>¡Gracias por su compra en Distribuidora Los Primos Ramírez!</p>
        <p>Documento generado automáticamenete por el Sistema de Gestión Operativa.</p>
    </div>

</body>
</html>