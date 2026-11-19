<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>@yield('titulo')</title>
    {{-- Ticket de 80 mm en PDF (dompdf). El alto del papel lo calcula Controller::entregarTicket(). --}}
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            font-size: 11px;
            color: #000;
            margin: 0;
            padding: 8px 10px;
        }
        .center { text-align: center; }
        .negrita { font-weight: bold; }
        .linea { border-top: 1px dashed #000; margin: 6px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 1px 0; vertical-align: top; font-size: 10px; }
        .col-cant { width: 14%; text-align: center; }
        .col-precio { width: 26%; text-align: right; }
        .total-row td { font-size: 13px; font-weight: bold; padding-top: 6px; }
        .footer { margin-top: 10px; font-size: 9px; text-align: center; }
    </style>
</head>
<body>
    <div class="center negrita">DISTRIBUIDORA</div>
    <div class="center negrita">"LOS PRIMOS RAMÍREZ"</div>
    @yield('contenido')
</body>
</html>
