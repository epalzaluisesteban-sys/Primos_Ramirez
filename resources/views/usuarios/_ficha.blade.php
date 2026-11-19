{{-- Ficha del usuario (ventana flotante del listado y página usuarios.show).
     Variables: $usuario, $actividad (ventas, ventas_mes, vendido_mes, compras, cajas). --}}
<div class="space-y-5">
    <div class="flex items-center gap-3">
        @if($usuario->fotoUrl())
            <img src="{{ $usuario->fotoUrl() }}" alt="{{ $usuario->name }}" class="w-14 h-14 rounded-full object-cover border border-stone-200 dark:border-stone-700 shrink-0">
        @else
            <span class="w-14 h-14 rounded-full bg-violet-100 text-violet-800 dark:bg-violet-900/40 dark:text-violet-300 font-bold text-lg flex items-center justify-center shrink-0">
                {{ strtoupper(substr($usuario->name, 0, 1)) }}
            </span>
        @endif
        <div class="min-w-0 text-xs text-stone-500 dark:text-stone-400 space-y-1">
            <p class="text-sm text-stone-700 dark:text-stone-200 truncate">{{ $usuario->email }}</p>
            <p class="flex flex-wrap items-center gap-1.5">
                @if($usuario->rol === 'Dueno')
                    <span class="insignia-violeta"><x-icon name="crown" class="w-3 h-3" /> Dueño</span>
                @elseif($usuario->rol === 'Encargado')
                    <span class="insignia-azul">Encargado</span>
                @else
                    <span class="insignia-gris">Obrero</span>
                @endif
                @if($usuario->activo)
                    <span class="insignia-verde">Activo</span>
                @else
                    <span class="insignia-roja">Sin acceso</span>
                @endif
            </p>
            <p>Último acceso:
                <span class="font-semibold text-stone-700 dark:text-stone-200">{{ $usuario->ultimo_acceso ? $usuario->ultimo_acceso->format('d/m/Y h:i A') . ' (' . $usuario->ultimo_acceso->diffForHumans() . ')' : 'Nunca' }}</span>
            </p>
        </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="caja-suave p-3">
            <p class="cifra-etiqueta">Ventas este mes</p>
            <p class="font-display text-lg font-bold text-stone-800 dark:text-stone-100">{{ $actividad['ventas_mes'] }}</p>
            <p class="text-[11px] text-stone-500 dark:text-stone-400">@usd($actividad['vendido_mes'])</p>
        </div>
        <div class="caja-suave p-3">
            <p class="cifra-etiqueta">Ventas en total</p>
            <p class="font-display text-lg font-bold text-stone-800 dark:text-stone-100">{{ $actividad['ventas'] }}</p>
        </div>
        <div class="caja-suave p-3">
            <p class="cifra-etiqueta">Compras</p>
            <p class="font-display text-lg font-bold text-stone-800 dark:text-stone-100">{{ $actividad['compras'] }}</p>
        </div>
        <div class="caja-suave p-3">
            <p class="cifra-etiqueta">Cajas que abrió</p>
            <p class="font-display text-lg font-bold text-stone-800 dark:text-stone-100">{{ $actividad['cajas'] }}</p>
        </div>
    </div>

    <div>
        <h3 class="titulo-tarjeta mb-2">Qué puede hacer</h3>
        <p class="ayuda-campo mb-2">{{ $usuario->nivelLabel() }}</p>
        <ul class="caja-suave px-3 py-2.5 space-y-1 text-xs text-stone-600 dark:text-stone-300">
            @foreach($usuario->permisosResumen() as $permiso)
                <li class="flex items-start gap-1.5"><x-icon name="check" class="w-3.5 h-3.5 mt-px shrink-0 text-violet-600 dark:text-violet-400" /> {{ $permiso }}</li>
            @endforeach
        </ul>
    </div>

    <a href="{{ route('usuarios.edit', $usuario) }}" data-editar class="btn-secundario w-full">
        <x-icon name="edit" class="w-4 h-4" /> Editar datos, rol o contraseña
    </a>
</div>
