@extends('layouts.app')

@section('title', 'Nuevo cliente')

@section('content')
    <div class="pagina-angosta">

        <x-encabezado titulo="Nuevo cliente" subtitulo="Regístralo para poder venderle a crédito (fiado)." :volver="route('clientes.index')" />

        <div class="tarjeta">
            <form action="{{ route('clientes.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                @include('clientes._form')

                <button type="submit" class="btn-primario w-full">
                    <x-icon name="plus" class="w-4 h-4" /> Guardar cliente
                </button>
            </form>
        </div>
    </div>
@endsection
