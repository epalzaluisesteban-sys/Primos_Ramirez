@extends('layouts.app')

@section('title', 'Nueva contraseña')

@section('content')
    <div class="min-h-[calc(100dvh-0.375rem)] flex items-center justify-center px-4 py-6">
        <div class="max-w-sm w-full">

            <div class="flex flex-col items-center mb-5">
                <x-logo class="w-16 h-16 mb-2" />
                <h1 class="font-display text-lg font-extrabold text-stone-900 dark:text-stone-100 text-center">Distribuidora "Los Primos Ramírez"</h1>
            </div>

            <div class="tarjeta">
                <h2 class="titulo-tarjeta text-lg">Poner una contraseña nueva</h2>
                <p class="text-xs text-stone-500 dark:text-stone-400 mt-0.5 mb-5">Escríbela dos veces. Después entras con ella.</p>

                <form class="space-y-4" action="{{ route('password.update') }}" method="POST">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div>
                        <label for="email" class="etiqueta">Correo electrónico</label>
                        <input id="email" name="email" type="email" required maxlength="255" autocomplete="username"
                               class="campo @error('email') border-rose-500 @enderror" value="{{ old('email', $email) }}">
                        @error('email')
                            <p class="error-campo">{{ $message }}</p>
                            <a href="{{ route('password.request') }}" class="inline-block mt-1 text-xs font-semibold text-emerald-700 dark:text-emerald-400 hover:underline">Pedir un enlace nuevo</a>
                        @enderror
                    </div>

                    <x-campo-clave name="password" label="Contraseña nueva" autocomplete="new-password"
                                   required minlength="8" maxlength="100" ayuda="Al menos 8 caracteres." />
                    <x-campo-clave name="password_confirmation" label="Repite la contraseña nueva" autocomplete="new-password"
                                   required minlength="8" maxlength="100" />

                    <button type="submit" class="btn-primario w-full">
                        <x-icon name="lock" class="w-4 h-4" /> Guardar contraseña
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        if (window.matchMedia('(pointer: fine)').matches) {
            document.getElementById('{{ $email ? 'password' : 'email' }}').focus();
        }
    </script>
@endpush
