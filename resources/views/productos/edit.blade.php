@extends('layouts.app')

@section('title', 'Editar producto')

@section('content')
    <div class="pagina-angosta">

        <x-encabezado titulo="Editar producto" :subtitulo="$producto->nombre" :volver="route('productos.index')" />

        <div class="tarjeta">
            <form action="{{ route('productos.update', $producto->id) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                @method('PUT')
                @include('productos._form')

                <button type="submit" class="btn-primario w-full">
                    <x-icon name="check" class="w-4 h-4" /> Guardar cambios
                </button>
            </form>
        </div>
    </div>
@endsection
