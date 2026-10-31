@extends('layouts.app')

@section('title', 'Nueva Venta')

@section('content')
    <div class="max-w-5xl mx-auto px-4 py-8">

        <div class="mb-6 flex items-center justify-between">
            <div>
                <h2 class="font-display text-xl font-bold text-stone-900 dark:text-stone-100">Registrar Venta</h2>
                <p class="text-sm text-stone-500 dark:text-stone-400 mt-0.5">Agrega los productos y confirma el cobro.</p>
            </div>
            <a href="{{ route('ventas.index') }}" class="flex items-center gap-1.5 text-sm font-semibold text-stone-500 hover:text-stone-800 dark:text-stone-400 dark:hover:text-stone-100">
                <x-icon name="arrow-left" class="w-4 h-4" /> Volver al historial
            </a>
        </div>

        <div class="bg-white dark:bg-stone-800 rounded-2xl shadow-sm border border-stone-200 dark:border-stone-700 p-6">

            @include('ventas._formulario', [
                'venta' => null,
                'accion' => route('ventas.store'),
                'textoBoton' => 'Finalizar y Cobrar Venta',
            ])
        </div>
    </div>
@endsection
