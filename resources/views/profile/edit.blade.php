@extends('layouts.app')

@section('title', 'Mi perfil')

@section('content')
    <div class="pagina-angosta">

        <x-encabezado titulo="Mi perfil" subtitulo="Tus datos personales y tu contraseña." />

        <div class="tarjeta sm:p-8">
            <div class="flex items-center gap-4 border-b border-stone-200 pb-6 mb-6 dark:border-stone-700">
                @if($user->fotoUrl())
                    <img src="{{ $user->fotoUrl() }}" alt="{{ $user->name }}" class="w-16 h-16 rounded-full object-cover border-2 border-emerald-600">
                @else
                    <div class="w-16 h-16 bg-emerald-100 text-emerald-800 font-bold text-2xl flex items-center justify-center rounded-full border-2 border-emerald-600 dark:text-emerald-400">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                @endif
                <div>
                    <h2 class="titulo-tarjeta text-lg">{{ $user->name }}</h2>
                    <p class="text-xs text-stone-500 font-medium dark:text-stone-400">Rol asignado: <span class="text-emerald-800 font-bold uppercase dark:text-emerald-400">{{ $user->rol }}</span></p>
                </div>
            </div>

            <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- Datos de Cuenta -->
                <div>
                    <h4 class="titulo-tarjeta mb-3 flex items-center gap-2">
                        <x-icon name="user-circle" class="w-4 h-4 text-emerald-600" /> Información personal
                    </h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="etiqueta">Nombre completo</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="campo dark:bg-stone-800">
                        </div>

                        <div>
                            <label class="etiqueta">Correo electrónico</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="campo dark:bg-stone-800">
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="etiqueta">Foto de perfil (opcional)</label>
                        <input type="file" name="foto" accept="image/*" class="campo-archivo">
                    </div>
                </div>

                <!-- Seguridad -->
                <div class="pt-5 border-t border-stone-200 dark:border-stone-700">
                    <h4 class="titulo-tarjeta mb-1 flex items-center gap-2">
                        <x-icon name="lock" class="w-4 h-4 text-emerald-600" /> Seguridad (Opcional)
                    </h4>
                    <p class="text-xs text-stone-500 mb-4 dark:text-stone-400">Deja estos campos en blanco si no deseas cambiar tu contraseña actual.</p>

                    <div class="space-y-4">
                        <x-campo-clave name="current_password" label="Contraseña actual" />

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <x-campo-clave name="password" label="Nueva contraseña" autocomplete="new-password" />

                            <x-campo-clave name="password_confirmation" label="Confirmar nueva contraseña" autocomplete="new-password" />
                        </div>
                    </div>
                </div>

                <div class="pt-4 text-right">
                    <button type="submit" class="btn-primario px-6">
                        <x-icon name="check" class="w-4 h-4" /> Guardar cambios
                    </button>
                </div>
            </form>
        </div>

    </div>
@endsection
