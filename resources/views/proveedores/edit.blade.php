@extends('layouts.app')

@section('title', 'Editar Proveedor')

@section('content')
    <div class="max-w-xl mx-auto px-4 py-8">

        <div class="mb-6 flex items-center justify-between">
            <div>
                <h2 class="font-display text-xl font-bold text-stone-900">Editar Proveedor</h2>
                <p class="text-sm text-stone-500 mt-0.5">{{ $proveedor->nombre }}</p>
            </div>
            <a href="{{ route('proveedores.index') }}" class="flex items-center gap-1.5 text-sm font-semibold text-stone-500 hover:text-stone-800">
                <x-icon name="arrow-left" class="w-4 h-4" /> Volver
            </a>
        </div>

        @if($errors->any())
            <div class="flex items-center gap-2.5 bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl mb-6 text-sm">
                <x-icon name="alert-triangle" class="w-4 h-4 shrink-0 text-rose-500" /> {{ $errors->first() }}
            </div>
        @endif

        <div class="bg-white rounded-2xl shadow-sm border border-stone-200 p-6">
            <form action="{{ route('proveedores.update', $proveedor->id) }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                @include('proveedores._form')

                <button type="submit" class="w-full flex items-center justify-center gap-2 bg-emerald-700 hover:bg-emerald-800 text-white font-semibold py-2.5 px-4 rounded-xl shadow-sm border border-stone-200 transition text-sm">
                    <x-icon name="check" class="w-4 h-4" /> Guardar Cambios
                </button>
            </form>
        </div>
    </div>
@endsection
