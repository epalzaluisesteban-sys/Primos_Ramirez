@extends('layouts.app')

@section('title', 'Iniciar sesión')

@section('content')
    {{-- Alto exacto de la ventana menos la franja de colores del layout (h-1.5), para que no aparezca scroll --}}
    <div class="min-h-[calc(100dvh-0.375rem)] flex items-center justify-center px-4 py-6">
        <div class="max-w-sm w-full">

            <div class="flex flex-col items-center mb-5">
                <x-logo class="w-16 h-16 mb-2" />
                <h1 class="font-display text-lg font-extrabold text-stone-900 dark:text-stone-100 text-center">Distribuidora "Los Primos Ramírez"</h1>
                <p class="text-sm text-stone-500 dark:text-stone-400">Sistema de gestión</p>
            </div>

            <div class="tarjeta">
                <h2 class="titulo-tarjeta text-lg">Iniciar sesión</h2>
                <p class="text-xs text-stone-500 dark:text-stone-400 mt-0.5 mb-5">Entra con el correo y la contraseña que te dio el dueño.</p>

                @if($errors->any())
                    <div class="aviso-rojo mb-4 text-xs">
                        <x-icon name="alert-triangle" class="w-4 h-4 shrink-0 text-rose-500" /> {{ $errors->first() }}
                    </div>
                @endif

                <form class="space-y-4" action="{{ route('login.post') }}" method="POST">
                    @csrf
                    <div>
                        <label for="email" class="etiqueta">Correo electrónico</label>
                        <input id="email" name="email" type="email" required autofocus autocomplete="username" class="campo" placeholder="usuario@primosramirez.com" value="{{ old('email') }}">
                    </div>
                    <x-campo-clave name="password" label="Contraseña" required />

                    <button type="submit" class="btn-primario w-full">
                        <x-icon name="lock" class="w-4 h-4" /> Entrar
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
