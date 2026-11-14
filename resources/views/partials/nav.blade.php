{{-- Barra superior compartida. El menú se define UNA vez (arreglo $menu) y se dibuja
     en escritorio y móvil, agrupado por TAREA del día a día. Cada opción lleva una
     línea de ayuda ('ayuda') que explica para qué sirve. La fuente Manrope se carga en layouts/app.blade.php. --}}
@php
    $usuarioNav = auth()->user();
    $menu = [
        ['label' => 'Inicio', 'icon' => 'leaf', 'route' => 'dashboard', 'match' => ['dashboard']],
        ['label' => 'Ventas', 'icon' => 'cart', 'match' => ['ventas.*', 'caja.*'], 'items' => [
            ['label' => 'Nueva venta', 'icon' => 'plus', 'route' => 'ventas.create', 'match' => ['ventas.create'], 'ayuda' => 'Registrar lo que se le vende a un cliente'],
            ['label' => 'Ventas registradas', 'icon' => 'cart', 'route' => 'ventas.index', 'match' => ['ventas.index', 'ventas.edit', 'ventas.show', 'ventas.papelera'], 'ayuda' => 'Ver, imprimir, editar o eliminar ventas'],
            ['label' => 'Caja del día', 'icon' => 'banknote', 'route' => 'caja.index', 'match' => ['caja.*'], 'ayuda' => 'Abrir la caja en la mañana y cerrarla al final'],
        ]],
        // Todos los roles: el obrero entra para cobrar los abonos de los fiados
        ['label' => 'Clientes', 'icon' => 'users', 'route' => 'clientes.index', 'match' => ['clientes.*']],
    ];

    if ($usuarioNav->esEncargadoOSuperior()) {
        $menu[] = ['label' => 'Inventario', 'icon' => 'box', 'match' => ['productos.*', 'categorias.*', 'mermas.*', 'compras.*', 'proveedores.*'], 'items' => [
            ['label' => 'Productos', 'icon' => 'box', 'route' => 'productos.index', 'match' => ['productos.*'], 'ayuda' => 'Precios, existencias y alertas de stock (filtros)'],
            ['label' => 'Recibir mercancía', 'icon' => 'inbox-down', 'route' => 'compras.index', 'match' => ['compras.*'], 'ayuda' => 'Anotar lo que llega del proveedor (suma al stock)'],
            ['label' => 'Proveedores', 'icon' => 'truck', 'route' => 'proveedores.index', 'match' => ['proveedores.*'], 'ayuda' => 'Datos de contacto y compras a cada proveedor'],
            ['label' => 'Registrar pérdida', 'icon' => 'package-x', 'route' => 'mermas.index', 'match' => ['mermas.*'], 'ayuda' => 'Producto dañado, vencido o extraviado (resta del stock)'],
            ['label' => 'Categorías', 'icon' => 'tag', 'route' => 'categorias.index', 'match' => ['categorias.*'], 'ayuda' => 'Grupos de productos (frutas, verduras…)'],
        ]];
        // La tasa BCV no va aquí: se abre desde la insignia de la barra superior (escritorio y celular)
        $menu[] = ['label' => 'Reportes', 'icon' => 'chart-bar', 'match' => ['reportes.*', 'estadisticas.*', 'historial.*'], 'items' => [
            ['label' => 'Ganancias', 'icon' => 'chart-bar', 'route' => 'reportes.ganancias', 'match' => ['reportes.*'], 'ayuda' => 'Cuánto se ganó por producto y categoría'],
            ['label' => 'Estadísticas', 'icon' => 'pie', 'route' => 'estadisticas.index', 'match' => ['estadisticas.*'], 'ayuda' => 'Tendencias y predicción de demanda'],
            ['label' => 'Historial de movimientos', 'icon' => 'clock', 'route' => 'historial.index', 'match' => ['historial.*'], 'ayuda' => 'Todo lo que se hizo en el sistema, por fecha'],
        ]];
    }

    if ($usuarioNav->esDueno()) {
        $menu[] = ['label' => 'Administración', 'icon' => 'crown', 'match' => ['usuarios.*', 'respaldos.*'], 'violeta' => true, 'items' => [
            ['label' => 'Personal', 'icon' => 'users', 'route' => 'usuarios.index', 'match' => ['usuarios.*'], 'ayuda' => 'Quién entra al sistema y qué puede hacer'],
            ['label' => 'Respaldo y restauración', 'icon' => 'database', 'route' => 'respaldos.index', 'match' => ['respaldos.*'], 'ayuda' => 'Guardar una copia de la información o recuperarla'],
        ]];
    }

    $claseActiva = fn ($e) => !empty($e['violeta'])
        ? 'bg-violet-50 text-violet-800 dark:bg-violet-900/30 dark:text-violet-300'
        : 'bg-emerald-50 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300';
    $claseInactiva = 'text-stone-500 hover:text-stone-800 hover:bg-stone-50/50 dark:text-stone-400 dark:hover:text-stone-100 dark:hover:bg-stone-800/50';
@endphp
<nav class="sticky top-0 z-40 bg-white/90 dark:bg-stone-900/90 backdrop-blur border-b border-stone-300 dark:border-stone-700">
    {{-- Más ancha que el contenido (.pagina): el menú de escritorio necesita el espacio en laptops --}}
    <div class="max-w-[96rem] mx-auto px-4">
        <div class="flex items-center justify-between gap-3 h-16">

            <!-- Marca -->
            {{-- En el celular solo el logo: el nombre no cabe junto a la tasa y el botón del menú --}}
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 shrink-0" aria-label="Primos Ramírez, inicio">
                <x-logo class="w-10 h-10 shrink-0" />
                <span class="hidden sm:inline font-display text-stone-900 dark:text-stone-100 font-extrabold text-[15px] leading-tight whitespace-nowrap">
                    Primos Ramírez
                </span>
            </a>

            <!-- Enlaces (escritorio) -->
            <div class="hidden xl:flex items-center gap-0.5 2xl:gap-1 min-w-0">
                @foreach($menu as $entrada)
                    @php $activo = request()->routeIs($entrada['match']); @endphp
                    @if(isset($entrada['items']))
                        <div class="relative" data-dropdown>
                            <button type="button" data-dropdown-toggle
                                    class="flex items-center gap-1.5 px-2.5 2xl:px-3 py-2 rounded-md text-sm font-semibold whitespace-nowrap transition {{ $activo ? $claseActiva($entrada) : $claseInactiva }}">
                                <x-icon name="{{ $entrada['icon'] }}" class="w-4 h-4 hidden 2xl:block" /> {{ $entrada['label'] }}
                                <x-icon name="chevron-down" class="w-3.5 h-3.5" />
                            </button>
                            <div data-dropdown-menu class="hidden absolute left-0 mt-2 w-72 menu-desplegable">
                                @foreach($entrada['items'] as $item)
                                    <a href="{{ route($item['route']) }}" class="flex items-start gap-2.5 px-4 py-2.5 text-sm {{ request()->routeIs($item['match']) ? 'bg-emerald-50 text-emerald-800 font-semibold dark:bg-emerald-900/30 dark:text-emerald-300' : 'text-stone-600 dark:text-stone-300 hover:bg-stone-50/50 dark:hover:bg-stone-700/50' }}">
                                        <x-icon name="{{ $item['icon'] }}" class="w-4 h-4 text-stone-500 shrink-0 mt-0.5" />
                                        <span>
                                            <span class="block">{{ $item['label'] }}</span>
                                            @isset($item['ayuda'])
                                                <span class="block text-[11px] font-normal text-stone-400 dark:text-stone-500 leading-snug">{{ $item['ayuda'] }}</span>
                                            @endisset
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <a href="{{ route($entrada['route']) }}"
                           class="flex items-center gap-1.5 px-2.5 2xl:px-3 py-2 rounded-md text-sm font-semibold whitespace-nowrap transition {{ $activo ? $claseActiva($entrada) : $claseInactiva }}">
                            <x-icon name="{{ $entrada['icon'] }}" class="w-4 h-4 hidden 2xl:block" /> {{ $entrada['label'] }}
                        </a>
                    @endif
                @endforeach
            </div>

            <!-- Notificaciones + Tema + Usuario (escritorio) -->
            <div class="hidden xl:flex items-center gap-1.5 2xl:gap-2 shrink-0">

                {{-- La tarea más frecuente, siempre a un clic --}}
                @unless(request()->routeIs('ventas.create'))
                    <a href="{{ route('ventas.create') }}" class="btn-primario py-2 whitespace-nowrap">
                        <x-icon name="plus" class="w-4 h-4" /> Nueva venta
                    </a>
                @endunless

                <x-tasa-badge :tasa="$tasaActual ?? null" />

                <button type="button" onclick="alternarTema()" class="p-2.5 rounded-md hover:bg-stone-50/50 dark:hover:bg-stone-800/50 transition text-stone-500 dark:text-stone-400" title="Cambiar tema claro/oscuro">
                    <x-icon name="sun" class="w-5 h-5 dark:hidden" />
                    <x-icon name="moon" class="w-5 h-5 hidden dark:block" />
                </button>

                <div class="relative" data-dropdown>
                    <button type="button" data-dropdown-toggle class="relative p-2.5 rounded-md hover:bg-stone-50/50 dark:hover:bg-stone-800/50 transition text-stone-500 dark:text-stone-400">
                        <x-icon name="bell" class="w-5 h-5" />
                        @if($notificaciones->count() > 0)
                            <span class="absolute top-1 right-1 w-4 h-4 bg-rose-600 text-white text-[10px] font-bold rounded-full flex items-center justify-center">
                                {{ $notificaciones->count() }}
                            </span>
                        @endif
                    </button>
                    <div data-dropdown-menu class="hidden absolute right-0 mt-2 w-80 menu-desplegable">
                        <div class="px-4 py-2.5 border-b border-stone-200 dark:border-stone-700">
                            <p class="text-sm font-bold text-stone-800 dark:text-stone-100">Notificaciones</p>
                        </div>
                        <div class="max-h-80 overflow-y-auto">
                            @forelse($notificaciones as $n)
                                {{-- Sin url (Obrero en avisos de productos): se muestra solo como información --}}
                                <{{ $n['url'] ? 'a' : 'div' }} @if($n['url']) href="{{ $n['url'] }}" @endif class="flex items-start gap-2.5 px-4 py-3 text-sm text-stone-600 dark:text-stone-300 {{ $n['url'] ? 'hover:bg-stone-50/50 dark:hover:bg-stone-700/50' : '' }} border-b border-stone-300 dark:border-stone-700">
                                    <x-icon name="{{ $n['icono'] }}" class="w-4 h-4 shrink-0 mt-0.5 {{ match($n['color']) { 'rose' => 'text-rose-500', 'amber' => 'text-amber-500', 'blue' => 'text-blue-500', default => 'text-stone-500' } }}" />
                                    <span>{{ $n['texto'] }}</span>
                                </{{ $n['url'] ? 'a' : 'div' }}>
                            @empty
                                <p class="px-4 py-6 text-center text-sm text-stone-500 dark:text-stone-400 flex items-center justify-center gap-1.5">
                                    <x-icon name="check-circle" class="w-4 h-4 text-emerald-500" /> Todo en orden
                                </p>
                            @endforelse
                        </div>
                        @if(auth()->user()->esEncargadoOSuperior())
                            <a href="{{ route('productos.index', ['filtro' => 'alertas']) }}" class="block px-4 py-2.5 text-xs font-bold text-emerald-800 dark:text-emerald-400 text-center border-t border-stone-200 dark:border-stone-700 hover:bg-stone-50/50 dark:hover:bg-stone-700/50">
                                Ver productos con alerta →
                            </a>
                        @endif
                    </div>
                </div>

                <div class="relative" data-dropdown>
                <button type="button" data-dropdown-toggle class="flex items-center gap-2 pl-1.5 pr-2.5 py-1.5 rounded-md hover:bg-stone-50/50 dark:hover:bg-stone-800/50 transition">
                    @if(auth()->user()->fotoUrl())
                        <img src="{{ auth()->user()->fotoUrl() }}" alt="{{ auth()->user()->name }}" class="w-7 h-7 rounded-full object-cover">
                    @else
                        <span class="w-7 h-7 rounded-full bg-emerald-700 text-white font-bold text-xs flex items-center justify-center">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </span>
                    @endif
                    <span class="hidden 2xl:inline text-stone-800 dark:text-stone-100 text-sm font-semibold whitespace-nowrap">{{ \Illuminate\Support\Str::of(auth()->user()->name)->before(' ') }}</span>
                    <x-icon name="chevron-down" class="w-3.5 h-3.5 text-stone-500 dark:text-stone-400" />
                </button>
                <div data-dropdown-menu class="hidden absolute right-0 mt-2 w-52 menu-desplegable">
                    <div class="px-4 py-2.5 border-b border-stone-200 dark:border-stone-700">
                        <p class="text-[11px] text-stone-500 dark:text-stone-400 uppercase tracking-wide">Sesión iniciada como</p>
                        <p class="text-sm font-bold text-stone-800 dark:text-stone-100">{{ auth()->user()->rolTexto() }}</p>
                    </div>
                    <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-stone-600 dark:text-stone-300 hover:bg-stone-50/50 dark:hover:bg-stone-700/50">
                        <x-icon name="user-circle" class="w-4 h-4 text-stone-500" /> Editar mi perfil
                    </a>
                    <a href="{{ route('ayuda.index') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-stone-600 dark:text-stone-300 hover:bg-stone-50/50 dark:hover:bg-stone-700/50">
                        <x-icon name="ayuda" class="w-4 h-4 text-stone-500" /> Cómo usar el sistema
                    </a>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/20 font-semibold">
                            <x-icon name="logout" class="w-4 h-4" /> Cerrar sesión
                        </button>
                    </form>
                </div>
                </div>
            </div>

            <!-- Tasa + botón menú (celular y tablet) -->
            <div class="xl:hidden flex items-center gap-2 shrink-0">
                <x-tasa-badge :tasa="$tasaActual ?? null" />
                <button type="button" data-menu-movil-abrir aria-controls="menu-movil" aria-expanded="false" aria-label="Abrir menú"
                        class="-mr-2 p-2 rounded-md text-stone-600 hover:bg-stone-100 dark:text-stone-300 dark:hover:bg-stone-800">
                    <x-icon name="menu" class="w-6 h-6" />
                </button>
            </div>
        </div>
    </div>
</nav>

{{-- Menú lateral del celular y tablet (mismo $menu que escritorio). Va fuera de <nav> porque el backdrop-blur
     de la barra haría que el panel fijo se ubique respecto a ella. El panel tiene su propio scroll y la página
     de atrás queda bloqueada mientras está abierto. --}}
<div id="menu-movil" data-menu-movil class="xl:hidden fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-label="Menú">
    <div data-menu-movil-cerrar class="absolute inset-0 bg-stone-900/50 dark:bg-black/60 opacity-0 transition-opacity duration-150"></div>

    <aside data-menu-movil-panel class="absolute inset-y-0 right-0 w-80 max-w-[85vw] flex flex-col bg-white dark:bg-stone-900 border-l border-stone-200 dark:border-stone-700 shadow-xl translate-x-full transition-transform duration-150">
        <div class="shrink-0 flex items-center justify-between gap-3 h-16 px-4 border-b border-stone-200 dark:border-stone-700">
            <span class="flex items-center gap-2.5 min-w-0">
                <x-logo class="w-9 h-9 shrink-0" />
                <span class="font-display font-extrabold text-[15px] text-stone-900 dark:text-stone-100 truncate">Primos Ramírez</span>
            </span>
            <button type="button" data-menu-movil-cerrar aria-label="Cerrar menú" class="-mr-2 p-2 rounded-md text-stone-600 hover:bg-stone-100 dark:text-stone-300 dark:hover:bg-stone-800">
                <x-icon name="x" class="w-6 h-6" />
            </button>
        </div>

        <div class="flex-1 min-h-0 overflow-y-auto overscroll-contain px-3 py-3">
            @foreach($menu as $entrada)
                @if(isset($entrada['items']))
                    <p class="px-3 pt-4 pb-1 first:pt-1 text-[11px] font-bold uppercase tracking-wide text-stone-500 dark:text-stone-400">{{ $entrada['label'] }}</p>
                    @foreach($entrada['items'] as $item)
                        <a href="{{ route($item['route']) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-semibold {{ request()->routeIs($item['match']) ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300' : 'text-stone-700 hover:bg-stone-100 dark:text-stone-200 dark:hover:bg-stone-800' }}" @if(request()->routeIs($item['match'])) aria-current="page" @endif>
                            <x-icon name="{{ $item['icon'] }}" class="w-5 h-5 shrink-0 opacity-70" /> {{ $item['label'] }}
                        </a>
                    @endforeach
                @else
                    @php $activoMovil = request()->routeIs($entrada['match']); @endphp
                    <a href="{{ route($entrada['route']) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-semibold {{ $activoMovil ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300' : 'text-stone-700 hover:bg-stone-100 dark:text-stone-200 dark:hover:bg-stone-800' }} {{ $loop->first ? '' : 'mt-3' }}" @if($activoMovil) aria-current="page" @endif>
                        <x-icon name="{{ $entrada['icon'] }}" class="w-5 h-5 shrink-0 opacity-70" /> {{ $entrada['label'] }}
                    </a>
                @endif
            @endforeach
        </div>

        {{-- Cuenta: siempre visible al pie del panel, sin tener que desplazarse --}}
        <div class="shrink-0 border-t border-stone-200 dark:border-stone-700 px-3 py-3 space-y-1 pb-[max(0.75rem,env(safe-area-inset-bottom))]">
            <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-semibold text-stone-700 hover:bg-stone-100 dark:text-stone-200 dark:hover:bg-stone-800">
                <x-icon name="user-circle" class="w-5 h-5 shrink-0 opacity-70" />
                <span class="min-w-0 truncate">{{ $usuarioNav->name }} <span class="font-normal text-stone-500 dark:text-stone-400">· {{ $usuarioNav->rolTexto() }}</span></span>
            </a>
            <a href="{{ route('ayuda.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-semibold {{ request()->routeIs('ayuda.*') ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300' : 'text-stone-700 hover:bg-stone-100 dark:text-stone-200 dark:hover:bg-stone-800' }}">
                <x-icon name="ayuda" class="w-5 h-5 shrink-0 opacity-70" /> Cómo usar el sistema
            </a>
            <div class="grid grid-cols-2 gap-1">
                <button type="button" onclick="alternarTema()" class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-semibold text-stone-700 hover:bg-stone-100 dark:text-stone-200 dark:hover:bg-stone-800">
                    <x-icon name="moon" class="w-5 h-5 shrink-0 opacity-70 dark:hidden" />
                    <x-icon name="sun" class="w-5 h-5 shrink-0 opacity-70 hidden dark:block" />
                    <span class="dark:hidden">Modo oscuro</span><span class="hidden dark:inline">Modo claro</span>
                </button>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-semibold text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-900/20">
                        <x-icon name="logout" class="w-5 h-5 shrink-0" /> Salir
                    </button>
                </form>
            </div>
        </div>
    </aside>
</div>

<script>
    document.querySelectorAll('[data-dropdown]').forEach(function (wrapper) {
        var toggle = wrapper.querySelector('[data-dropdown-toggle]');
        var menu = wrapper.querySelector('[data-dropdown-menu]');
        toggle.addEventListener('click', function (e) {
            e.stopPropagation();
            document.querySelectorAll('[data-dropdown-menu]').forEach(function (m) {
                if (m !== menu) m.classList.add('hidden');
            });
            menu.classList.toggle('hidden');
        });
    });
    document.addEventListener('click', function () {
        document.querySelectorAll('[data-dropdown-menu]').forEach(function (m) { m.classList.add('hidden'); });
    });

    // Menú lateral del celular: bloquea el scroll de la página, se cierra con la X, tocando fuera, con Escape
    // o al elegir una opción, y devuelve el foco al botón que lo abrió.
    (function () {
        var abrir = document.querySelector('[data-menu-movil-abrir]');
        var menu = document.querySelector('[data-menu-movil]');
        if (!abrir || !menu) return;
        var panel = menu.querySelector('[data-menu-movil-panel]');
        var fondo = menu.querySelector('div[data-menu-movil-cerrar]');

        function abrirMenu() {
            menu.classList.remove('hidden');
            document.documentElement.classList.add('overflow-hidden');
            abrir.setAttribute('aria-expanded', 'true');
            requestAnimationFrame(function () {
                panel.classList.remove('translate-x-full');
                fondo.classList.remove('opacity-0');
            });
            var activo = panel.querySelector('[aria-current="page"]');
            if (activo) activo.scrollIntoView({ block: 'center' });
            panel.querySelector('button[data-menu-movil-cerrar]').focus();
        }
        function cerrarMenu() {
            if (menu.classList.contains('hidden')) return;
            panel.classList.add('translate-x-full');
            fondo.classList.add('opacity-0');
            document.documentElement.classList.remove('overflow-hidden');
            abrir.setAttribute('aria-expanded', 'false');
            setTimeout(function () { menu.classList.add('hidden'); }, 150);
            abrir.focus();
        }

        abrir.addEventListener('click', abrirMenu);
        menu.querySelectorAll('[data-menu-movil-cerrar]').forEach(function (el) { el.addEventListener('click', cerrarMenu); });
        menu.querySelectorAll('a[href]').forEach(function (a) { a.addEventListener('click', cerrarMenu); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') cerrarMenu(); });
        // Si se agranda la ventana hasta el menú de escritorio, no dejar la página bloqueada
        window.matchMedia('(min-width: 80rem)').addEventListener('change', function (m) { if (m.matches) cerrarMenu(); });
    })();
</script>
