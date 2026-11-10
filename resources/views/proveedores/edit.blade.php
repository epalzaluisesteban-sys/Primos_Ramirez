@extends('layouts.app')

@section('title', 'Editar proveedor')

@section('content')
    <div class="pagina-angosta">

        <x-encabezado titulo="Editar proveedor" :subtitulo="$proveedor->nombre" :volver="route('proveedores.index')" />

        <div class="tarjeta">
            <form action="{{ route('proveedores.update', $proveedor->id) }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                @include('proveedores._form')

                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-1">
                    <a href="{{ route('proveedores.index') }}" class="btn-secundario">Cancelar</a>
                    <button type="submit" class="btn-primario">
                        <x-icon name="check" class="w-4 h-4" /> Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
