@extends('layouts.app')

@section('title', 'Confirmar restauración')

@section('content')
    <div class="pagina-angosta">
        <x-encabezado titulo="Confirmar restauración" subtitulo="Revisa qué contiene el respaldo antes de reemplazar la información actual." :volver="route('respaldos.index')" />

        <div class="tarjeta mb-6">
            <h2 class="titulo-tarjeta">Respaldo del {{ $fecha }}</h2>
            <p class="text-sm text-stone-600 dark:text-stone-300 mt-1">
                Hecho por {{ $manifiesto['creado_por'] ?? 'desconocido' }} · {{ $manifiesto['fotos'] ?? 0 }} fotos
            </p>

            <div class="overflow-x-auto mt-4">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Información</th>
                            <th class="text-right">En el respaldo</th>
                            <th class="text-right">Ahora</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($comparacion as $fila)
                            <tr>
                                <td>{{ $fila['nombre'] }}</td>
                                <td class="text-right font-semibold text-stone-800 dark:text-stone-100">{{ $fila['respaldo'] }}</td>
                                <td class="text-right {{ $fila['respaldo'] !== $fila['actual'] ? 'text-amber-700 dark:text-amber-400 font-semibold' : '' }}">{{ $fila['actual'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="aviso-rojo mb-6">
            <x-icon name="alert-triangle" class="w-4 h-4 mt-0.5 shrink-0" />
            <div class="space-y-1">
                <p><strong>Todo lo registrado después de esa fecha se perderá</strong> (la columna "Ahora" se reemplaza por "En el respaldo").</p>
                <p>Antes de cambiar nada se guarda automáticamente un respaldo de lo que hay ahora, por si necesitas volver atrás.</p>
                <p>
                    Al terminar se cerrará la sesión de todos. Entra con un usuario del respaldo
                    @if($duenos->isNotEmpty())
                        (Dueño: {{ $duenos->join(', ', ' o ') }}).
                    @endif
                </p>
            </div>
        </div>

        <form action="{{ route('respaldos.restaurar', $pendiente) }}" method="POST" class="tarjeta space-y-4">
            @csrf
            <div>
                <label class="etiqueta" for="confirmacion">Escribe RESTAURAR para confirmar</label>
                <input id="confirmacion" name="confirmacion" type="text" required autocomplete="off" spellcheck="false"
                       class="campo uppercase @error('confirmacion') border-rose-500 @enderror" placeholder="RESTAURAR">
                @error('confirmacion') <p class="error-campo">{{ $message }}</p> @enderror
            </div>

            <x-campo-clave name="password" label="Tu contraseña" required />

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-1">
                <a href="{{ route('respaldos.index') }}" class="btn-secundario">Cancelar</a>
                <button type="submit" class="btn-peligro">
                    <x-icon name="database" class="w-4 h-4" /> Restaurar respaldo
                </button>
            </div>
        </form>
    </div>
@endsection
