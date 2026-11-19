@extends('layouts.app')

@section('title', 'Caja del ' . $caja->fechaCorta())

@section('content')
    <div class="pagina-angosta">
        <x-encabezado :titulo="'Caja del ' . $caja->fechaCorta()" subtitulo="Cuadre del día: lo que entró, lo que salió y lo que se contó al cerrar." :volver="route('caja.index')" />

        <div class="tarjeta">
            @include('caja._detalle')
        </div>
    </div>
@endsection
