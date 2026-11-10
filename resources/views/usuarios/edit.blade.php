@extends('layouts.app')

@section('title', 'Editar usuario')

@section('content')
    @php $esYo = $usuario->id === auth()->id(); @endphp
    <div class="pagina-angosta">

        <x-encabezado titulo="Editar usuario" :subtitulo="$usuario->name . ' · ' . $usuario->email" :volver="route('usuarios.index')" />

        <div class="tarjeta">
            <form action="{{ route('usuarios.update', $usuario->id) }}" method="POST" enctype="multipart/form-data" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="flex items-center gap-4">
                    <div class="shrink-0">
                        @if($usuario->fotoUrl())
                            <img src="{{ $usuario->fotoUrl() }}" alt="{{ $usuario->name }}" class="w-16 h-16 rounded-full object-cover border border-stone-300 dark:border-stone-700">
                        @else
                            <span class="w-16 h-16 rounded-full bg-violet-100 text-violet-800 dark:bg-violet-900/40 dark:text-violet-300 font-bold text-lg flex items-center justify-center">
                                {{ strtoupper(substr($usuario->name, 0, 1)) }}
                            </span>
                        @endif
                    </div>
                    <div class="flex-1">
                        <label class="etiqueta" for="foto">Foto (Opcional)</label>
                        <input id="foto" type="file" name="foto" accept="image/*" class="campo-archivo">
                        <p class="ayuda-campo">JPG o PNG, máximo 2MB. Si no eliges una, se mantiene la actual.</p>
                        @error('foto') <p class="error-campo">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="etiqueta" for="name">Nombre completo</label>
                    <input id="name" type="text" name="name" value="{{ old('name', $usuario->name) }}" required class="campo @error('name') border-rose-500 @enderror">
                    @error('name') <p class="error-campo">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="etiqueta">Correo electrónico</label>
                    <input type="text" value="{{ $usuario->email }}" disabled class="campo">
                    <p class="ayuda-campo">El correo no se puede cambiar desde aquí.</p>
                </div>

                @include('usuarios._rol', ['seleccionado' => old('rol', $usuario->rol), 'bloqueado' => $esYo, 'idCampo' => $usuario->id])

                <x-campo-clave name="password" label="Nueva contraseña (opcional)" placeholder="Déjala en blanco para no cambiarla" autocomplete="new-password" minlength="8" />

                @unless($esYo)
                    <label class="caja-suave flex items-start gap-3 p-3 cursor-pointer">
                        <input type="hidden" name="activo" value="0">
                        <input type="checkbox" name="activo" value="1" @checked(old('activo', $usuario->activo)) class="casilla mt-0.5 text-violet-700 focus:ring-violet-500">
                        <span>
                            <span class="block text-sm font-semibold text-stone-700 dark:text-stone-200">Puede iniciar sesión</span>
                            <span class="block text-[11px] text-stone-500 dark:text-stone-400">Desmárcalo para quitarle el acceso. Sus ventas y movimientos se conservan.</span>
                        </span>
                    </label>
                @endunless

                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-1">
                    <a href="{{ route('usuarios.index') }}" class="btn-secundario">Cancelar</a>
                    <button type="submit" class="btn bg-violet-800 hover:bg-violet-900 text-white">
                        <x-icon name="check" class="w-4 h-4" /> Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
