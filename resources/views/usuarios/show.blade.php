@extends('layouts.app')

@section('title', $usuario->name)

@section('content')
    <div class="pagina-angosta">
        <x-encabezado :titulo="$usuario->name" subtitulo="Su rol, lo que puede hacer y lo que ha registrado." :volver="route('usuarios.index')" />

        <div class="tarjeta">
            @include('usuarios._ficha')
        </div>
    </div>
@endsection
