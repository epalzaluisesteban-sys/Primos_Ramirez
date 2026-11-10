{{-- Selector de rol con la lista de lo que puede hacer cada uno, visible antes de guardar.
     Parámetros: $seleccionado (rol actual u old()), $bloqueado (true si no se puede cambiar). --}}
@php
    $roles = [
        'Obrero' => 'Obrero — solo ventas y caja',
        'Encargado' => 'Encargado — ventas, inventario y compras',
        'Dueno' => 'Dueño — acceso total y personal',
    ];
    $bloqueado = $bloqueado ?? false;
@endphp
<div data-selector-rol>
    <label class="etiqueta" for="rol-{{ $idCampo ?? 'nuevo' }}">Rol</label>
    <select id="rol-{{ $idCampo ?? 'nuevo' }}" name="rol" required @disabled($bloqueado) class="campo @error('rol') border-rose-500 @enderror">
        @unless($seleccionado)
            <option value="">— Elige un rol —</option>
        @endunless
        @foreach($roles as $valor => $texto)
            <option value="{{ $valor }}" @selected($seleccionado === $valor)
                    data-permisos='@json(\App\Models\User::permisosDeRol($valor))'>{{ $texto }}</option>
        @endforeach
    </select>
    @if($bloqueado)
        <input type="hidden" name="rol" value="{{ $seleccionado }}">
        <p class="ayuda-campo">No puedes cambiar tu propio rol.</p>
    @endif
    @error('rol') <p class="error-campo">{{ $message }}</p> @enderror

    <ul data-permisos-lista class="caja-suave mt-2 px-3 py-2.5 space-y-1 text-xs text-stone-600 dark:text-stone-300 {{ $seleccionado ? '' : 'hidden' }}">
        @foreach($seleccionado ? \App\Models\User::permisosDeRol($seleccionado) : [] as $permiso)
            <li class="flex items-start gap-1.5"><x-icon name="check" class="w-3.5 h-3.5 mt-px shrink-0 text-violet-600 dark:text-violet-400" /> {{ $permiso }}</li>
        @endforeach
    </ul>
    <template data-icono-permiso><x-icon name="check" class="w-3.5 h-3.5 mt-px shrink-0 text-violet-600 dark:text-violet-400" /></template>
</div>

@once
    @push('scripts')
        <script>
            // Al cambiar el rol, reescribe la lista de permisos con los del rol elegido.
            document.querySelectorAll('[data-selector-rol]').forEach(function (bloque) {
                var select = bloque.querySelector('select');
                var lista = bloque.querySelector('[data-permisos-lista]');
                var icono = bloque.querySelector('[data-icono-permiso]').innerHTML;

                select.addEventListener('change', function () {
                    var opcion = select.selectedOptions[0];
                    var permisos = opcion && opcion.value ? JSON.parse(opcion.dataset.permisos) : [];
                    lista.classList.toggle('hidden', permisos.length === 0);
                    lista.innerHTML = '';
                    permisos.forEach(function (texto) {
                        var li = document.createElement('li');
                        li.className = 'flex items-start gap-1.5';
                        li.innerHTML = icono;
                        li.appendChild(document.createTextNode(' ' + texto));
                        lista.appendChild(li);
                    });
                });
            });
        </script>
    @endpush
@endonce
