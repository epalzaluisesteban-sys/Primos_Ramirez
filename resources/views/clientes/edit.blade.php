@extends('layouts.app')

@section('title', 'Editar cliente')

@section('content')
    <div class="pagina-angosta">

        <x-encabezado titulo="Editar cliente" :subtitulo="$cliente->nombre" :volver="route('clientes.index')" />

        <div class="tarjeta">
            <form action="{{ route('clientes.update', $cliente->id) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                @method('PUT')
                @include('clientes._form')

                <button type="submit" class="btn-primario w-full">
                    <x-icon name="check" class="w-4 h-4" /> Guardar cambios
                </button>
            </form>
        </div>
    </div>
@endsection
