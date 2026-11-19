@extends('layouts.app')

@section('title', 'Gasto')

@section('content')
    <div class="pagina-angosta">
        <x-encabezado titulo="Gasto" subtitulo="Dinero que salió del cajón ese día." :volver="route('gastos.index')" />

        <div class="tarjeta">
            @include('gastos._ficha')
        </div>
    </div>
@endsection
