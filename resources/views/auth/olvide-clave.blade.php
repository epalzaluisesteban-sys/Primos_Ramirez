@extends('layouts.app')

@section('title', 'Recuperar contraseña')

@section('content')
    <div class="min-h-[calc(100dvh-0.375rem)] flex items-center justify-center px-4 py-6">
        <div class="max-w-sm w-full">

            <div class="flex flex-col items-center mb-5">
                <x-logo class="w-16 h-16 mb-2" />
                <h1 class="font-display text-lg font-extrabold text-stone-900 dark:text-stone-100 text-center">Distribuidora "Los Primos Ramírez"</h1>
            </div>

            <div class="tarjeta">
                <h2 class="titulo-tarjeta text-lg">Recuperar contraseña</h2>

                @if(session('enlace_enviado'))
                    <div class="aviso-azul mt-4">
                        <x-icon name="check" class="w-4 h-4 shrink-0 mt-0.5" />
                        <span>Si <strong>{{ session('enlace_enviado') }}</strong> tiene una cuenta, te llegó un correo con un enlace para poner una contraseña nueva. Vence en {{ config('auth.passwords.users.expire') }} minutos.</span>
                    </div>
                    <p class="ayuda-campo mt-3">Revisa también la carpeta de correo no deseado. Si no te llega, pídele al dueño que te cambie la contraseña desde Personal.</p>

                    <a href="{{ route('login') }}" class="btn-primario w-full mt-5">
                        <x-icon name="arrow-left" class="w-4 h-4" /> Volver a iniciar sesión
                    </a>
                @elseif(!$correoDisponible)
                    <div class="aviso-ambar mt-4">
                        <x-icon name="alert-triangle" class="w-4 h-4 shrink-0 mt-0.5" />
                        <span>El sistema todavía no puede enviar correos. Pídele al dueño que te cambie la contraseña desde <strong>Personal</strong>.</span>
                    </div>

                    <a href="{{ route('login') }}" class="btn-primario w-full mt-5">
                        <x-icon name="arrow-left" class="w-4 h-4" /> Volver a iniciar sesión
                    </a>
                @else
                    <p class="text-xs text-stone-500 dark:text-stone-400 mt-0.5 mb-5">Escribe el correo con el que entras y te mandamos un enlace para poner una contraseña nueva.</p>

                    <form class="space-y-4" action="{{ route('password.email') }}" method="POST">
                        @csrf
                        <div>
                            <label for="email" class="etiqueta">Correo electrónico</label>
                            <input id="email" name="email" type="email" required maxlength="255" autocomplete="username"
                                   class="campo @error('email') border-rose-500 @enderror" placeholder="usuario@primosramirez.com" value="{{ old('email') }}">
                            @error('email') <p class="error-campo">{{ $message }}</p> @enderror
                        </div>

                        <button type="submit" class="btn-primario w-full">
                            <x-icon name="key" class="w-4 h-4" /> Enviar enlace
                        </button>
                    </form>

                    <p class="ayuda-campo mt-4">¿No tienes acceso a tu correo? Pídele al dueño que te cambie la contraseña desde Personal.</p>

                    <a href="{{ route('login') }}" class="btn-secundario w-full mt-4">
                        <x-icon name="arrow-left" class="w-4 h-4" /> Volver a iniciar sesión
                    </a>
                @endif
            </div>
        </div>
    </div>
@endsection
