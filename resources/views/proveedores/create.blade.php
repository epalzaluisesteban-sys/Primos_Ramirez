@extends('layouts.app')

@section('title', 'Nuevo proveedor')

@section('content')
    <div class="pagina-angosta">

        <x-encabezado titulo="Nuevo proveedor" subtitulo="Después podrás elegirlo al registrar una compra." :volver="route('proveedores.index')" />

        <div class="tarjeta">
            <form action="{{ route('proveedores.store') }}" method="POST" class="space-y-4">
                @csrf
                @include('proveedores._form')

                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-1">
                    <a href="{{ route('proveedores.index') }}" class="btn-secundario">Cancelar</a>
                    <button type="submit" class="btn-primario">
                        <x-icon name="plus" class="w-4 h-4" /> Guardar proveedor
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
