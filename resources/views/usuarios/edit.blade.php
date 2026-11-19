@extends('layouts.app')

@section('title', 'Editar usuario')

@section('content')
    <div class="pagina-angosta">

        <x-encabezado titulo="Editar usuario" :subtitulo="$usuario->name . ' · ' . $usuario->email" :volver="route('usuarios.index')" />

        <div class="tarjeta">
            <form action="{{ route('usuarios.update', $usuario->id) }}" method="POST" enctype="multipart/form-data" class="space-y-5">
                @csrf
                @method('PUT')

                @include('usuarios._campos')

                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-1">
                    <a href="{{ route('usuarios.index') }}" class="btn-secundario">Cancelar</a>
                    <button type="submit" class="btn bg-violet-800 hover:bg-violet-900 text-white">
                        <x-icon name="check" class="w-4 h-4" /> Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
