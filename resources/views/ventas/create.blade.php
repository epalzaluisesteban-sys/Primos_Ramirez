@extends('layouts.app')

@section('title', 'Nueva venta')

@section('content')
    <div class="pagina">

        <x-encabezado titulo="Nueva venta" subtitulo="Agrega los productos, elige cómo paga y confirma el cobro." :volver="route('ventas.index')" />

        @include('ventas._formulario', [
            'venta' => null,
            'accion' => route('ventas.store'),
            'textoBoton' => 'Finalizar y cobrar venta',
        ])
    </div>
@endsection
