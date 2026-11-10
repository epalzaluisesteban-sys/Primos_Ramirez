@extends('layouts.app')

@section('title', 'Nueva venta')

@section('content')
    <div class="pagina">

        <x-encabezado titulo="Nueva venta" subtitulo="Agrega los productos y confirma el cobro." :volver="route('ventas.index')" />

        <div class="tarjeta">

            @include('ventas._formulario', [
                'venta' => null,
                'accion' => route('ventas.store'),
                'textoBoton' => 'Finalizar y cobrar venta',
            ])
        </div>
    </div>
@endsection
