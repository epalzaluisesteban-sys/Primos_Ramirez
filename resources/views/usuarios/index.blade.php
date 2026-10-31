@extends('layouts.app')

@section('title', 'Gestión de Personal')

@section('content')
    <div class="max-w-7xl mx-auto px-4 py-8">

        <div class="mb-6">
            <h2 class="font-display text-xl font-bold text-stone-900 flex items-center gap-2">
                <x-icon name="users" class="w-5 h-5 text-violet-800" /> Control de Acceso y Personal
            </h2>
            <p class="text-sm text-stone-500 mt-0.5">Administra quién puede entrar al sistema y con qué permisos.</p>
        </div>

        @if(session('success'))
            <div class="flex items-center gap-2.5 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl mb-6 text-sm">
                <x-icon name="check" class="w-4 h-4 text-emerald-600" /> {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="flex items-center gap-2.5 bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl mb-6 text-sm">
                <x-icon name="alert-triangle" class="w-4 h-4 shrink-0 text-rose-500" /> {{ $errors->first() }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- Formulario para Registrar Nuevo Trabajador -->
            <div class="bg-white rounded-2xl shadow-sm border border-stone-200 p-6 h-fit border-t-4 border-violet-700">
                <h3 class="font-display text-base font-bold text-stone-800 mb-4">Registrar Nuevo Trabajador</h3>

                <form action="{{ route('usuarios.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-semibold text-stone-600 uppercase mb-1.5">Nombre Completo</label>
                        <input type="text" name="name" value="{{ old('name') }}" required placeholder="Ej. Carlos Mendoza" class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-violet-500 focus:border-violet-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-stone-600 uppercase mb-1.5">Correo Electrónico</label>
                        <input type="email" name="email" value="{{ old('email') }}" required placeholder="usuario@empresa.com" class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-violet-500 focus:border-violet-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-stone-600 uppercase mb-1.5">Contraseña Inicial</label>
                        <input type="password" name="password" required placeholder="••••••••" class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-violet-500 focus:border-violet-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-stone-600 uppercase mb-1.5">Rol / Permisos</label>
                        <select name="rol" required class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-violet-500 focus:border-violet-500">
                            <option value="">-- Seleccionar Rol --</option>
                            <option value="Obrero">Obrero (Solo Ventas)</option>
                            <option value="Encargado">Encargado (Ventas, Inventario y Compras)</option>
                            <option value="Dueno">Dueño (Acceso Total y Personal)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-stone-600 uppercase mb-1.5">Foto (Opcional)</label>
                        <input type="file" name="foto" accept="image/*" class="w-full text-sm text-stone-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-violet-50 file:text-violet-700 file:font-semibold hover:file:bg-violet-100">
                    </div>

                    <button type="submit" class="w-full flex items-center justify-center gap-2 bg-violet-800 hover:bg-violet-900 text-white font-semibold py-2.5 px-4 rounded-xl shadow-sm border border-stone-200 transition text-sm">
                        <x-icon name="plus" class="w-4 h-4" /> Guardar Usuario
                    </button>
                </form>
            </div>

            <!-- Tabla de Usuarios Registrados -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-stone-200 p-6">
                <div class="flex justify-between items-center mb-4 border-b border-stone-200 pb-4">
                    <h3 class="font-display text-base font-bold text-stone-800">Personal Autorizado en el Sistema</h3>
                    <span class="bg-violet-100 text-violet-800 font-bold text-xs px-3 py-1.5 rounded-full">
                        Total: {{ $usuarios->count() }} usuarios
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-stone-600">
                        <thead class="text-stone-500 uppercase text-xs border-b border-stone-200">
                            <tr>
                                <th class="py-3 px-4 font-semibold">Usuario</th>
                                <th class="py-3 px-4 font-semibold">Correo</th>
                                <th class="py-3 px-4 font-semibold text-center">Rol / Nivel</th>
                                <th class="py-3 px-4 font-semibold text-center">Estado</th>
                                <th class="py-3 px-4 font-semibold">Último Acceso</th>
                                <th class="py-3 px-4 font-semibold text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            @foreach($usuarios as $user)
                                <tr class="hover:bg-stone-50">
                                    <td class="py-3 px-4 font-bold text-stone-900">
                                        <div class="flex items-center gap-2.5">
                                            @if($user->fotoUrl())
                                                <img src="{{ $user->fotoUrl() }}" alt="{{ $user->name }}" class="w-8 h-8 rounded-full object-cover border border-stone-200 shrink-0">
                                            @else
                                                <span class="w-8 h-8 rounded-full bg-violet-100 text-violet-800 font-bold text-xs flex items-center justify-center border border-stone-200 shrink-0">
                                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                                </span>
                                            @endif
                                            <span>
                                                {{ $user->name }}
                                                @if($user->id === auth()->id())
                                                    <span class="text-xs bg-emerald-100 text-emerald-800 font-normal px-2 py-0.5 rounded ml-1">(Tú)</span>
                                                @endif
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-xs">{{ $user->email }}</td>
                                    <td class="py-3 px-4 text-center" title="{{ implode(' · ', $user->permisosResumen()) }}">
                                        @if($user->rol === 'Dueno')
                                            <span class="inline-flex items-center gap-1 bg-violet-100 text-violet-800 font-extrabold text-xs px-2.5 py-1 rounded-full border border-violet-300">
                                                <x-icon name="crown" class="w-3 h-3" /> Dueño
                                            </span>
                                        @elseif($user->rol === 'Encargado')
                                            <span class="bg-blue-100 text-blue-800 font-bold text-xs px-2.5 py-1 rounded-full border border-blue-300">
                                                Encargado
                                            </span>
                                        @else
                                            <span class="bg-stone-100 text-stone-800 font-semibold text-xs px-2.5 py-1 rounded-full border border-stone-300">
                                                Obrero
                                            </span>
                                        @endif
                                        <p class="text-[10px] text-stone-400 mt-1">{{ $user->nivelLabel() }}</p>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        @if($user->activo)
                                            <span class="bg-emerald-100 text-emerald-800 font-bold text-xs px-2.5 py-1 rounded-full border border-emerald-300">Activo</span>
                                        @else
                                            <span class="bg-rose-100 text-rose-800 font-bold text-xs px-2.5 py-1 rounded-full border border-rose-300">Inactivo</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-xs">{{ $user->ultimo_acceso ? $user->ultimo_acceso->diffForHumans() : 'Nunca' }}</td>
                                    <td class="py-3 px-4 text-center">
                                        <div class="flex justify-center items-center gap-2">
                                            <a href="{{ route('usuarios.edit', $user->id) }}" class="p-2 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-800 transition inline-flex" title="Editar">
                                                <x-icon name="edit" class="w-4 h-4" />
                                            </a>
                                            @if($user->id !== auth()->id())
                                                <form action="{{ route('usuarios.destroy', $user->id) }}" method="POST" data-confirm="¿Confirma que desea revocar el acceso a este usuario?" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="p-2 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-800 transition inline-flex" title="Revocar acceso">
                                                        <x-icon name="trash" class="w-4 h-4" />
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-xs text-stone-500 italic">Protegido</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>
@endsection
