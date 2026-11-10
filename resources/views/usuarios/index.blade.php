@extends('layouts.app')

@section('title', 'Personal')

@section('content')
    <div class="pagina">

        <x-encabezado titulo="Personal" subtitulo="Quién puede entrar al sistema y qué puede hacer cada uno.">
            <span class="insignia-violeta">{{ $usuarios->count() }} {{ $usuarios->count() === 1 ? 'usuario' : 'usuarios' }}</span>
        </x-encabezado>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

            <!-- Registrar trabajador -->
            <div class="tarjeta">
                <h3 class="titulo-tarjeta mb-4">Registrar trabajador</h3>

                <form action="{{ route('usuarios.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <div>
                        <label class="etiqueta" for="name">Nombre completo</label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required placeholder="Ej. Carlos Mendoza" class="campo @error('name') border-rose-500 @enderror">
                        @error('name') <p class="error-campo">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="etiqueta" for="email">Correo electrónico</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required placeholder="usuario@primosramirez.com" class="campo @error('email') border-rose-500 @enderror">
                        <p class="ayuda-campo">Lo usará para iniciar sesión.</p>
                        @error('email') <p class="error-campo">{{ $message }}</p> @enderror
                    </div>

                    <x-campo-clave name="password" label="Contraseña inicial" placeholder="Mínimo 8 caracteres" autocomplete="new-password" required minlength="8" />

                    @include('usuarios._rol', ['seleccionado' => old('rol')])

                    <div>
                        <label class="etiqueta" for="foto">Foto (Opcional)</label>
                        <input id="foto" type="file" name="foto" accept="image/*" class="campo-archivo">
                        <p class="ayuda-campo">JPG o PNG, máximo 2MB.</p>
                        @error('foto') <p class="error-campo">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit" class="btn w-full bg-violet-800 hover:bg-violet-900 text-white">
                        <x-icon name="plus" class="w-4 h-4" /> Guardar usuario
                    </button>
                </form>
            </div>

            <!-- Personal registrado -->
            <div class="tarjeta lg:col-span-2 p-0 overflow-hidden">
                <div class="px-6 py-4 border-b border-stone-200 dark:border-stone-700">
                    <h3 class="titulo-tarjeta">Personal registrado</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="tabla">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Rol</th>
                                <th class="text-center">Estado</th>
                                <th>Último acceso</th>
                                <th class="text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($usuarios as $user)
                                @php
                                    $esYo = $user->id === auth()->id();
                                    $tieneHistorial = $conMovimientos->has($user->id);
                                @endphp
                                <tr class="{{ $user->activo ? '' : 'opacity-60' }}">
                                    <td>
                                        <div class="flex items-center gap-3">
                                            @if($user->fotoUrl())
                                                <img src="{{ $user->fotoUrl() }}" alt="{{ $user->name }}" class="w-9 h-9 rounded-full object-cover border border-stone-200 dark:border-stone-700 shrink-0">
                                            @else
                                                <span class="w-9 h-9 rounded-full bg-violet-100 text-violet-800 dark:bg-violet-900/40 dark:text-violet-300 font-bold text-sm flex items-center justify-center shrink-0">
                                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                                </span>
                                            @endif
                                            <div class="min-w-0">
                                                <p class="font-bold text-stone-900 dark:text-stone-100 flex items-center gap-1.5">
                                                    {{ $user->name }}
                                                    @if($esYo) <span class="insignia-verde py-0.5 px-2 text-[10px]">Tú</span> @endif
                                                </p>
                                                <p class="text-xs text-stone-500 dark:text-stone-400 truncate">{{ $user->email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td title="{{ implode(' · ', $user->permisosResumen()) }}">
                                        @if($user->rol === 'Dueno')
                                            <span class="insignia-violeta"><x-icon name="crown" class="w-3 h-3" /> Dueño</span>
                                        @elseif($user->rol === 'Encargado')
                                            <span class="insignia-azul">Encargado</span>
                                        @else
                                            <span class="insignia-gris">Obrero</span>
                                        @endif
                                        <p class="text-[10px] text-stone-400 dark:text-stone-500 mt-1">{{ $user->nivelLabel() }}</p>
                                    </td>
                                    <td class="text-center">
                                        @if($user->activo)
                                            <span class="insignia-verde">Activo</span>
                                        @else
                                            <span class="insignia-roja">Sin acceso</span>
                                        @endif
                                    </td>
                                    <td class="text-xs whitespace-nowrap">{{ $user->ultimo_acceso ? $user->ultimo_acceso->diffForHumans() : 'Nunca' }}</td>
                                    <td>
                                        <div class="flex justify-end items-center gap-2">
                                            <a href="{{ route('usuarios.edit', $user->id) }}" class="btn-icono-ambar" title="Editar datos, rol o contraseña">
                                                <x-icon name="edit" class="w-4 h-4" />
                                            </a>

                                            @if($esYo)
                                                <span class="btn-icono-gris cursor-default opacity-60" title="No puedes quitarte el acceso a ti mismo">
                                                    <x-icon name="lock" class="w-4 h-4" />
                                                </span>
                                            @elseif($tieneHistorial || !$user->activo)
                                                {{-- Con movimientos no se puede borrar: se le quita o devuelve el acceso --}}
                                                <form action="{{ route('usuarios.activo', $user->id) }}" method="POST" class="inline"
                                                      @if($user->activo) data-confirm="¿Quitarle el acceso a {{ $user->name }}? No podrá iniciar sesión, pero sus ventas y movimientos se conservan." @endif>
                                                    @csrf
                                                    @method('PATCH')
                                                    @if($user->activo)
                                                        <button type="submit" class="btn-icono-gris" title="Quitar acceso (se conserva su historial)">
                                                            <x-icon name="ban" class="w-4 h-4" />
                                                        </button>
                                                    @else
                                                        <button type="submit" class="btn-icono-verde" title="Devolver acceso">
                                                            <x-icon name="check-circle" class="w-4 h-4" />
                                                        </button>
                                                    @endif
                                                </form>
                                            @endif

                                            @if(!$esYo && !$tieneHistorial)
                                                <form action="{{ route('usuarios.destroy', $user->id) }}" method="POST" class="inline"
                                                      data-confirm="¿Eliminar a {{ $user->name }}? No tiene movimientos registrados, así que se borrará por completo.">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-icono-rojo" title="Eliminar (no tiene movimientos)">
                                                        <x-icon name="trash" class="w-4 h-4" />
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <p class="px-6 py-3 border-t border-stone-200 dark:border-stone-700 text-[11px] text-stone-500 dark:text-stone-400 flex flex-wrap gap-x-4 gap-y-1">
                    <span class="inline-flex items-center gap-1"><x-icon name="ban" class="w-3.5 h-3.5" /> Quitar acceso: para quien ya registró ventas u otros movimientos.</span>
                    <span class="inline-flex items-center gap-1"><x-icon name="trash" class="w-3.5 h-3.5" /> Eliminar: solo si nunca registró nada.</span>
                </p>
            </div>

        </div>

    </div>
@endsection
