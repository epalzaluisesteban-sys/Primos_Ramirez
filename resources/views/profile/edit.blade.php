@extends('layouts.app')

@section('title', 'Mi Perfil')

@section('content')
    <div class="max-w-3xl mx-auto px-4 py-8">

        <div class="mb-6">
            <h2 class="font-display text-xl font-bold text-stone-900">Ajustes de Mi Cuenta</h2>
            <p class="text-sm text-stone-500 mt-0.5">Actualiza tus datos personales y tu contraseña.</p>
        </div>

        @if(session('success'))
            <div class="flex items-center gap-2.5 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl mb-6 text-sm">
                <x-icon name="check" class="w-4 h-4 text-emerald-600" /> {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="flex items-start gap-2.5 bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl mb-6 text-sm">
                <x-icon name="alert-triangle" class="w-4 h-4 mt-0.5 shrink-0 text-rose-500" />
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white rounded-2xl shadow-sm border border-stone-200 p-6 sm:p-8">
            <div class="flex items-center gap-4 border-b border-stone-200 pb-6 mb-6">
                @if($user->fotoUrl())
                    <img src="{{ $user->fotoUrl() }}" alt="{{ $user->name }}" class="w-16 h-16 rounded-full object-cover border-2 border-emerald-600">
                @else
                    <div class="w-16 h-16 bg-emerald-100 text-emerald-800 font-bold text-2xl flex items-center justify-center rounded-full border-2 border-emerald-600">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                @endif
                <div>
                    <h3 class="text-xl font-bold text-stone-800">{{ $user->name }}</h3>
                    <p class="text-xs text-stone-500 font-medium">Rol asignado: <span class="text-emerald-800 font-bold uppercase">{{ $user->rol }}</span></p>
                </div>
            </div>

            <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- Datos de Cuenta -->
                <div>
                    <h4 class="font-display text-sm font-bold text-stone-800 mb-3 flex items-center gap-2">
                        <x-icon name="user-circle" class="w-4 h-4 text-emerald-600" /> Información Personal
                    </h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-stone-600 uppercase mb-1.5">Nombre Completo</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-emerald-500 focus:border-emerald-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-stone-600 uppercase mb-1.5">Correo Electrónico</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-xs font-semibold text-stone-600 uppercase mb-1.5">Foto de Perfil (Opcional)</label>
                        <input type="file" name="foto" accept="image/*" class="w-full text-sm text-stone-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:text-emerald-700 file:font-semibold hover:file:bg-emerald-100">
                    </div>
                </div>

                <!-- Seguridad -->
                <div class="pt-5 border-t border-stone-200">
                    <h4 class="font-display text-sm font-bold text-stone-800 mb-1 flex items-center gap-2">
                        <x-icon name="lock" class="w-4 h-4 text-emerald-600" /> Seguridad (Opcional)
                    </h4>
                    <p class="text-xs text-stone-500 mb-4">Deja estos campos en blanco si no deseas cambiar tu contraseña actual.</p>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-stone-600 uppercase mb-1.5">Contraseña Actual</label>
                            <input type="password" name="current_password" class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-emerald-500 focus:border-emerald-500" placeholder="••••••••">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-stone-600 uppercase mb-1.5">Nueva Contraseña</label>
                                <input type="password" name="password" class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-emerald-500 focus:border-emerald-500" placeholder="••••••••">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-stone-600 uppercase mb-1.5">Confirmar Nueva Contraseña</label>
                                <input type="password" name="password_confirmation" class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-emerald-500 focus:border-emerald-500" placeholder="••••••••">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-4 text-right">
                    <button type="submit" class="inline-flex items-center gap-2 bg-emerald-700 hover:bg-emerald-800 text-white font-semibold py-2.5 px-6 rounded-xl shadow-sm border border-stone-200 transition text-sm">
                        <x-icon name="check" class="w-4 h-4" /> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>

    </div>
@endsection
