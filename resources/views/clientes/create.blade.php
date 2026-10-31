@extends('layouts.app')

@section('title', 'Nuevo Cliente')

@section('content')
    <div class="max-w-xl mx-auto px-4 py-8">

        <div class="mb-6 flex items-center justify-between">
            <div>
                <h2 class="font-display text-xl font-bold text-stone-900 dark:text-stone-100">Nuevo Cliente</h2>
                <p class="text-sm text-stone-500 dark:text-stone-400 mt-0.5">Regístralo para poder venderle a crédito (fiado).</p>
            </div>
            <a href="{{ route('clientes.index') }}" class="flex items-center gap-1.5 text-sm font-semibold text-stone-500 dark:text-stone-400 hover:text-stone-800 dark:hover:text-stone-100">
                <x-icon name="arrow-left" class="w-4 h-4" /> Volver
            </a>
        </div>

        <div class="bg-white dark:bg-stone-800 rounded-2xl shadow-sm border border-stone-200 dark:border-stone-700 p-6">
            <form action="{{ route('clientes.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                @include('clientes._form')

                <button type="submit" class="w-full flex items-center justify-center gap-2 bg-emerald-700 hover:bg-emerald-800 text-white font-semibold py-2.5 px-4 rounded-xl shadow-sm transition text-sm">
                    <x-icon name="plus" class="w-4 h-4" /> Guardar Cliente
                </button>
            </form>
        </div>
    </div>
@endsection
