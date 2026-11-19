@extends('layouts.app')

@section('title', 'Pérdida registrada')

@section('content')
    <div class="pagina-angosta">
        <x-encabezado titulo="Pérdida registrada" subtitulo="Lo que se perdió, por qué y quién lo anotó." :volver="route('mermas.index')" />

        <div class="tarjeta">
            @include('mermas._ficha')
        </div>
    </div>
@endsection
