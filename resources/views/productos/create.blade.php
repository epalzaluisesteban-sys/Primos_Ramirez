@extends('layouts.app')

@section('title', 'Nuevo producto')

@section('content')
    <div class="pagina-angosta">

        <x-encabezado titulo="Nuevo producto" subtitulo="Agrega un producto al inventario." :volver="route('productos.index')" />

        <div class="tarjeta">
            <form action="{{ route('productos.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                @include('productos._form')

                <button type="submit" class="btn-primario w-full">
                    <x-icon name="plus" class="w-4 h-4" /> Guardar producto
                </button>
            </form>
        </div>
    </div>
@endsection
