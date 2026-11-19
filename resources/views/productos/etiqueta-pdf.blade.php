<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Etiqueta {{ $producto->nombre }}</title>
    {{-- Etiqueta de 100 x 45 mm (ProductoController::etiquetaPdf). dompdf dibuja los SVG de App\Support\Etiqueta. --}}
    <style>
        @page { margin: 0; }
        body { font-family: Arial, sans-serif; color: #111; margin: 0; padding: 10px 12px; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: middle; }
        .nombre { font-size: 15px; font-weight: bold; }
        .marca { font-size: 10px; color: #555; }
        .precio { font-size: 18px; font-weight: bold; color: #15803d; margin-top: 4px; }
        .precio-bs { font-size: 10px; color: #333; }
        .codigo { font-size: 9px; color: #555; text-align: center; }
        .sin-codigo { font-size: 9px; color: #888; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td>
                <div class="nombre">{{ $producto->nombre }}</div>
                @if($producto->marca)<div class="marca">{{ $producto->marca }}</div>@endif
                <div class="precio">@usd($producto->precio)</div>
                @if(\App\Support\Moneda::tasa() > 0)<div class="precio-bs">@bs($producto->precio)</div>@endif
                <div class="marca">por {{ $producto->unidadLabel() }}</div>
            </td>
            <td style="width: 70px; text-align: center;">
                <img src="{{ \App\Support\Etiqueta::comoImagen($qr) }}" style="width: 64px; height: 64px;" alt="QR">
                <div class="codigo">{{ \App\Support\Etiqueta::textoQr($producto) }}</div>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="padding-top: 6px; text-align: center;">
                @if($codigoBarras)
                    <img src="{{ \App\Support\Etiqueta::comoImagen($codigoBarras) }}" style="height: 40px; max-width: 250px;" alt="Código de barras">
                    <div class="codigo">{{ $producto->codigo_barras }}</div>
                @else
                    <div class="sin-codigo">{{ $producto->codigo_barras ? 'Código de barras inválido: revísalo en Editar' : 'Sin código de barras registrado' }}</div>
                @endif
            </td>
        </tr>
    </table>
</body>
</html>
