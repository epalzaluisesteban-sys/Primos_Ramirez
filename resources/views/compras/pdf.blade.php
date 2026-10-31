<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Compras</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
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
            margin-bottom: 15px;
        }
        .info-table td {
            padding: 4px 0;
        }
        .tabla-compras {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .tabla-compras th {
            background-color: #15803d;
            color: #ffffff;
            text-align: left;
            padding: 6px 8px;
            font-size: 10px;
        }
        .tabla-compras td {
            border-bottom: 1px solid #ddd;
            padding: 6px 8px;
        }
        .total-box {
            text-align: right;
            font-size: 14px;
            font-weight: bold;
            color: #15803d;
            margin-top: 10px;
        }
        .footer {
            margin-top: 30px;
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
        <p>Historial de Entradas de Mercancía (Compras)</p>
    </div>

    <table class="info-table">
        <tr>
            <td><strong>Fecha de generación:</strong> {{ now()->format('d/m/Y H:i') }}</td>
            <td style="text-align: right;"><strong>Total de registros:</strong> {{ $compras->count() }}</td>
        </tr>
    </table>

    <table class="tabla-compras">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Producto</th>
                <th style="text-align: center;">Cantidad</th>
                <th style="text-align: right;">Costo U.</th>
                <th style="text-align: right;">Total</th>
                <th>Proveedor</th>
                <th>Registrado Por</th>
            </tr>
        </thead>
        <tbody>
            @forelse($compras as $compra)
                <tr>
                    <td>{{ $compra->fecha }}</td>
                    <td>{{ $compra->producto->nombre ?? 'Eliminado' }}</td>
                    <td style="text-align: center;">{{ $compra->cantidad }}</td>
                    <td style="text-align: right;">${{ number_format($compra->precio_compra, 2) }}</td>
                    <td style="text-align: right;">${{ number_format($compra->total, 2) }}</td>
                    <td>{{ $compra->nombreProveedor() }}</td>
                    <td>{{ $compra->usuario->name ?? 'Sistema' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 15px;">No hay compras registradas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="total-box">
        TOTAL INVERTIDO EN COMPRAS: ${{ number_format($totalGeneral, 2) }}
    </div>

    <div class="footer">
        <p>Documento generado automáticamente por el Sistema de Gestión Operativa.</p>
    </div>

</body>
</html>
