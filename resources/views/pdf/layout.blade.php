<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>@yield('titulo')</title>
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
        table.datos {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.datos th {
            background-color: #15803d;
            color: #ffffff;
            text-align: left;
            padding: 6px 8px;
            font-size: 10px;
        }
        table.datos td {
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
        <p>@yield('subtitulo')</p>
    </div>

    <table class="info-table">
        <tr>
            <td><strong>Fecha de generación:</strong> {{ now()->format('d/m/Y H:i') }}</td>
            <td style="text-align: right;">@yield('resumen')</td>
        </tr>
    </table>

    @yield('contenido')

    <div class="footer">
        <p>Documento generado automáticamente por el Sistema de Gestión Operativa.</p>
    </div>

</body>
</html>
