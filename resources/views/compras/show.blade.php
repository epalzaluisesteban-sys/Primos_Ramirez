@extends('layouts.app')

@section('title', 'Entrada de mercancía')

@section('content')
    <div class="pagina-angosta">
        <x-encabezado titulo="Entrada de mercancía" subtitulo="Lo que llegó, cuánto costó y a quién se le compró." :volver="route('compras.index')" />

        <div class="tarjeta">
            @include('compras._ficha')
        </div>
    </div>
@endsection
