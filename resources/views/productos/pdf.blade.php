@extends('pdf.layout')

@section('titulo', 'Inventario de Productos')
@section('subtitulo', 'Inventario General de Productos')
@section('resumen')
    <strong>Total de productos:</strong> {{ $productos->count() }}
@endsection

@section('contenido')
    <table class="datos">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Marca</th>
                <th>Categoría</th>
                <th>Ubicación</th>
                <th>Cód. Barras</th>
                <th style="text-align: right;">Precio Compra</th>
                <th style="text-align: right;">Precio Venta</th>
                <th style="text-align: right;">% Ganancia</th>
                <th style="text-align: center;">Stock</th>
                <th>Estado</th>
                <th>Vencimiento</th>
                <th style="text-align: right;">Valor Inventario</th>
            </tr>
        </thead>
        <tbody>
            @forelse($productos as $p)
                <tr>
                    <td>{{ $p->nombre }}</td>
                    <td>{{ $p->marca ?: '—' }}</td>
                    <td>{{ $p->categoria->nombre ?? 'Sin categoría' }}</td>
                    <td>{{ $p->ubicacion ?: '—' }}</td>
                    <td>{{ $p->codigo_barras ?: '—' }}</td>
                    <td style="text-align: right;">{{ $p->precio_compra !== null ? '$' . number_format($p->precio_compra, 2) : '—' }}</td>
                    <td style="text-align: right;">${{ number_format($p->precio, 2) }}</td>
                    <td style="text-align: right;">{{ $p->porcentajeGanancia() !== null ? number_format($p->porcentajeGanancia(), 1) . '%' : '—' }}</td>
                    <td style="text-align: center;">{{ $p->stock }} / min {{ $p->stock_minimo }}@if($p->stock_maximo) / max {{ $p->stock_maximo }}@endif</td>
                    <td>{{ $estados[$p->id] }}</td>
                    <td>{{ $p->fecha_vencimiento ? $p->fecha_vencimiento->format('d/m/Y') : '—' }}</td>
                    <td style="text-align: right;">${{ number_format($p->stock * $p->precio, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="12" style="text-align:center; padding:15px;">No hay productos registrados.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="total-box">
        VALOR TOTAL DE INVENTARIO: ${{ number_format($valorTotalInventario, 2) }}
    </div>
@endsection
