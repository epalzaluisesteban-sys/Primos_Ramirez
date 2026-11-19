@extends('layouts.app')

@section('title', $categoria->nombre)

@section('content')
    <div class="pagina-angosta">
        <x-encabezado :titulo="$categoria->nombre" subtitulo="Los productos de esta categoría y quién la distribuye." :volver="route('categorias.index')" />

        <div class="tarjeta">
            @include('categorias._ficha')
        </div>
    </div>
@endsection
