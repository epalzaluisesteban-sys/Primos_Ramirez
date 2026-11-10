@extends('layouts.app')

@section('title', 'Editar venta')

@section('content')
    <div class="pagina">

        <x-encabezado :titulo="'Editar venta #' . $venta->id" subtitulo="Corrige los productos o las cantidades. El stock se ajusta solo." :volver="route('ventas.index')" />

        <div class="tarjeta">

            @include('ventas._formulario', [
                'accion' => route('ventas.update', $venta->id),
                'textoBoton' => 'Guardar cambios',
            ])
        </div>
    </div>
@endsection
