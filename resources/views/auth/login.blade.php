@extends('layouts.app')

@section('title', 'Iniciar Sesión')

@section('content')
    <div class="min-h-screen flex items-center justify-center py-12 px-4">
        <div class="max-w-md w-full">

            <!-- Marca -->
            <div class="flex flex-col items-center mb-8">
                <span class="w-14 h-14 rounded-2xl bg-emerald-700 flex items-center justify-center shadow-sm mb-4">
                    <span class="text-white font-extrabold text-2xl font-display">R</span>
                </span>
                <h1 class="font-display text-xl font-extrabold text-stone-900 text-center">Distribuidora "Los Primos Ramírez"</h1>
                <p class="text-sm text-stone-500 mt-1">Sistema de gestión operativa</p>
                <div class="flex items-center gap-3 mt-3 text-stone-300">
                    <x-icon name="leaf" class="w-4 h-4 text-emerald-500" />
                    <x-icon name="apple" class="w-4 h-4 text-rose-500" />
                    <x-icon name="carrot" class="w-4 h-4 text-orange-500" />
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-stone-200 p-8 relative overflow-hidden">
                <div class="market-stripe absolute top-0 left-0 right-0 h-1.5"></div>
                <h2 class="font-display text-lg font-bold text-stone-900 mb-1">Iniciar Sesión</h2>
                <p class="text-xs text-stone-500 mb-6">Ingresa tus credenciales para acceder al sistema.</p>

                @if($errors->any())
                    <div class="flex items-center gap-2.5 bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl mb-6 text-xs">
                        <x-icon name="alert-triangle" class="w-4 h-4 shrink-0 text-rose-500" /> {{ $errors->first() }}
                    </div>
                @endif

                <form class="space-y-4" action="{{ route('login.post') }}" method="POST">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-stone-600 uppercase mb-1.5">Correo Electrónico</label>
                        <input id="email" name="email" type="email" required autofocus class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-emerald-500 focus:border-emerald-500" placeholder="usuario@primosramirez.com" value="{{ old('email') }}">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-stone-600 uppercase mb-1.5">Contraseña</label>
                        <input id="password" name="password" type="password" required class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-emerald-500 focus:border-emerald-500" placeholder="••••••••">
                    </div>

                    <button type="submit" class="w-full flex items-center justify-center gap-2 bg-emerald-700 hover:bg-emerald-800 text-white font-semibold py-3 px-4 rounded-xl shadow-sm border border-stone-200 transition text-sm">
                        <x-icon name="lock" class="w-4 h-4" /> Ingresar al Sistema
                    </button>
                </form>

                <!-- Accesos de prueba (Seeder) -->
                <div class="mt-6 border-t border-stone-200 pt-4">
                    <p class="text-[11px] text-stone-500 uppercase font-bold tracking-wide mb-2 text-center">Credenciales de prueba (Seeder)</p>
                    <div class="text-xs text-stone-600 bg-stone-50 p-3 rounded-lg border border-stone-200 text-center font-mono">
                        admin@primosramirez.com / 12345678
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
