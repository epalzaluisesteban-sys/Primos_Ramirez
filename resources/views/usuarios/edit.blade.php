@extends('layouts.app')

@section('title', 'Editar Usuario')

@section('content')
    <div class="max-w-xl mx-auto px-4 py-8">

        <div class="mb-6 flex items-center justify-between">
            <div>
                <h2 class="font-display text-xl font-bold text-stone-900">Editar Usuario</h2>
                <p class="text-sm text-stone-500 mt-0.5">{{ $usuario->name }}</p>
            </div>
            <a href="{{ route('usuarios.index') }}" class="flex items-center gap-1.5 text-sm font-semibold text-stone-500 hover:text-stone-800">
                <x-icon name="arrow-left" class="w-4 h-4" /> Volver
            </a>
        </div>

        @if($errors->any())
            <div class="flex items-center gap-2.5 bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-xl mb-6 text-sm">
                <x-icon name="alert-triangle" class="w-4 h-4 shrink-0 text-rose-500" /> {{ $errors->first() }}
            </div>
        @endif

        <div class="bg-white rounded-2xl shadow-sm border border-stone-200 p-6">
            <form action="{{ route('usuarios.update', $usuario->id) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                @method('PUT')

                <div class="flex items-center gap-4">
                    <div class="shrink-0">
                        @if($usuario->fotoUrl())
                            <img src="{{ $usuario->fotoUrl() }}" alt="{{ $usuario->name }}" class="w-16 h-16 rounded-full object-cover border border-stone-300">
                        @else
                            <span class="w-16 h-16 rounded-full bg-violet-100 text-violet-800 font-bold text-lg flex items-center justify-center border border-stone-300">
                                {{ strtoupper(substr($usuario->name, 0, 1)) }}
                            </span>
                        @endif
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-semibold text-stone-600 uppercase mb-1.5">Foto (Opcional)</label>
                        <input type="file" name="foto" accept="image/*" class="w-full text-sm text-stone-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-violet-50 file:text-violet-700 file:font-semibold hover:file:bg-violet-100">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-stone-600 uppercase mb-1.5">Nombre Completo</label>
                    <input type="text" name="name" value="{{ old('name', $usuario->name) }}" required class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 focus:ring-violet-500 focus:border-violet-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-stone-600 uppercase mb-1.5">Correo Electrónico</label>
                    <input type="text" value="{{ $usuario->email }}" disabled class="w-full rounded-lg border-stone-200 border p-2.5 text-sm bg-stone-100 text-stone-500">
                    <p class="text-[11px] text-stone-400 mt-1">El correo no se puede modificar desde aquí.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-stone-600 uppercase mb-1.5">Rol / Permisos</label>
                    <select name="rol" required {{ $usuario->id === auth()->id() ? 'disabled' : '' }} class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 focus:ring-violet-500 focus:border-violet-500">
                        <option value="Obrero" {{ old('rol', $usuario->rol) == 'Obrero' ? 'selected' : '' }}>Obrero (Solo Ventas)</option>
                        <option value="Encargado" {{ old('rol', $usuario->rol) == 'Encargado' ? 'selected' : '' }}>Encargado (Ventas, Inventario y Compras)</option>
                        <option value="Dueno" {{ old('rol', $usuario->rol) == 'Dueno' ? 'selected' : '' }}>Dueño (Acceso Total y Personal)</option>
                    </select>
                    @if($usuario->id === auth()->id())
                        <input type="hidden" name="rol" value="{{ $usuario->rol }}">
                        <p class="text-[11px] text-stone-400 mt-1">No puedes cambiar tu propio rol.</p>
                    @endif
                    <p class="text-xs text-stone-500 mt-2">{{ implode(' · ', $usuario->permisosResumen()) }}</p>
                </div>

                @if($usuario->id !== auth()->id())
                    <label class="flex items-center gap-2.5 pt-1">
                        <input type="hidden" name="activo" value="0">
                        <input type="checkbox" name="activo" value="1" {{ old('activo', $usuario->activo) ? 'checked' : '' }} class="rounded border-stone-300 text-violet-700 focus:ring-violet-500">
                        <span class="text-sm text-stone-700 font-semibold">Usuario activo (puede iniciar sesión)</span>
                    </label>
                @endif

                <div>
                    <label class="block text-xs font-semibold text-stone-600 uppercase mb-1.5">Nueva Contraseña (Opcional)</label>
                    <input type="password" name="password" placeholder="Dejar en blanco para no cambiarla" class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-violet-500 focus:border-violet-500">
                </div>

                <button type="submit" class="w-full flex items-center justify-center gap-2 bg-violet-800 hover:bg-violet-900 text-white font-semibold py-2.5 px-4 rounded-xl shadow-sm transition text-sm">
                    <x-icon name="check" class="w-4 h-4" /> Guardar Cambios
                </button>
            </form>
        </div>
    </div>
@endsection
