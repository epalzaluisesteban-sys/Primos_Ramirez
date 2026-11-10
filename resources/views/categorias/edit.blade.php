@extends('layouts.app')

@section('title', 'Editar categoría')

@section('content')
    <div class="pagina-angosta">

        <x-encabezado titulo="Editar categoría" :subtitulo="$categoria->nombre" :volver="route('categorias.index')" />

        <div class="tarjeta">
            <form action="{{ route('categorias.update', $categoria->id) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                @method('PUT')
                @include('categorias._form')

                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-1">
                    <a href="{{ route('categorias.index') }}" class="btn-secundario">Cancelar</a>
                    <button type="submit" class="btn-primario">
                        <x-icon name="check" class="w-4 h-4" /> Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
