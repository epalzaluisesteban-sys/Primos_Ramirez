{{-- Barra superior compartida. El menú se define UNA vez (arreglo $menu) y se dibuja
     en escritorio y móvil, agrupado por tarea. La fuente Manrope se carga en layouts/app.blade.php. --}}
@php
    $usuarioNav = auth()->user();
    $menu = [
        ['label' => 'Ventas', 'icon' => 'cart', 'route' => 'ventas.index', 'match' => ['ventas.*']],
        ['label' => 'Caja', 'icon' => 'banknote', 'route' => 'caja.index', 'match' => ['caja.*']],
    ];

    if ($usuarioNav->esEncargadoOSuperior()) {
        $menu[] = ['label' => 'Inventario', 'icon' => 'box', 'match' => ['productos.*', 'categorias.*', 'inventario.*', 'mermas.*'], 'items' => [
            ['label' => 'Productos', 'icon' => 'box', 'route' => 'productos.index', 'match' => ['productos.*']],
            ['label' => 'Categorías', 'icon' => 'tag', 'route' => 'categorias.index', 'match' => ['categorias.*']],
            ['label' => 'Alertas de Inventario', 'icon' => 'alert-triangle', 'route' => 'inventario.alertas', 'match' => ['inventario.*']],
            ['label' => 'Pérdidas', 'icon' => 'package-x', 'route' => 'mermas.index', 'match' => ['mermas.*']],
        ]];
        $menu[] = ['label' => 'Compras y Clientes', 'icon' => 'inbox-down', 'match' => ['compras.*', 'proveedores.*', 'clientes.*'], 'items' => [
            ['label' => 'Compras', 'icon' => 'inbox-down', 'route' => 'compras.index', 'match' => ['compras.*']],
            ['label' => 'Proveedores', 'icon' => 'truck', 'route' => 'proveedores.index', 'match' => ['proveedores.*']],
            ['label' => 'Clientes / Fiado', 'icon' => 'users', 'route' => 'clientes.index', 'match' => ['clientes.*']],
        ]];
        $menu[] = ['label' => 'Reportes', 'icon' => 'chart-bar', 'match' => ['reportes.*', 'estadisticas.*', 'historial.*'], 'items' => [
            ['label' => 'Ganancias', 'icon' => 'chart-bar', 'route' => 'reportes.ganancias', 'match' => ['reportes.*']],
            ['label' => 'Estadísticas', 'icon' => 'pie', 'route' => 'estadisticas.index', 'match' => ['estadisticas.*']],
            ['label' => 'Historial General', 'icon' => 'clock', 'route' => 'historial.index', 'match' => ['historial.*']],
        ]];
    }

    if ($usuarioNav->esDueno()) {
        $menu[] = ['label' => 'Personal', 'icon' => 'users', 'route' => 'usuarios.index', 'match' => ['usuarios.*'], 'violeta' => true];
    }

    $claseActiva = fn ($e) => !empty($e['violeta'])
        ? 'bg-violet-50 text-violet-800 dark:bg-violet-900/30 dark:text-violet-300'
        : 'bg-emerald-50 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300';
    $claseInactiva = 'text-stone-500 hover:text-stone-800 hover:bg-stone-50/50 dark:text-stone-400 dark:hover:text-stone-100 dark:hover:bg-stone-800/50';
@endphp
<nav class="sticky top-0 z-40 bg-white/90 dark:bg-stone-900/90 backdrop-blur border-b border-stone-300 dark:border-stone-700">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex items-center justify-between h-16">

            <!-- Marca -->
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 shrink-0">
                <span class="w-8 h-8 rounded-lg bg-emerald-700 flex items-center justify-center">
                    <span class="text-white font-extrabold text-sm" style="font-family:'Manrope',sans-serif">R</span>
                </span>
                <span class="hidden sm:block text-stone-900 dark:text-stone-100 font-extrabold text-[15px]" style="font-family:'Manrope',sans-serif">
                    Primos Ramírez
                </span>
            </a>

            <!-- Enlaces (escritorio) -->
            <div class="hidden md:flex items-center gap-1">
                @foreach($menu as $entrada)
                    @php $activo = request()->routeIs($entrada['match']); @endphp
                    @if(isset($entrada['items']))
                        <div class="relative" data-dropdown>
                            <button type="button" data-dropdown-toggle
                                    class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-semibold transition {{ $activo ? $claseActiva($entrada) : $claseInactiva }}">
                                <x-icon name="{{ $entrada['icon'] }}" class="w-4 h-4" /> {{ $entrada['label'] }}
                                <x-icon name="chevron-down" class="w-3.5 h-3.5" />
                            </button>
                            <div data-dropdown-menu class="hidden absolute left-0 mt-2 w-56 bg-white dark:bg-stone-800 rounded-xl shadow-lg border border-stone-200 dark:border-stone-700 ring-1 ring-stone-200 dark:ring-stone-700 py-1.5 overflow-hidden">
                                @foreach($entrada['items'] as $item)
                                    <a href="{{ route($item['route']) }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm {{ request()->routeIs($item['match']) ? 'bg-emerald-50 text-emerald-800 font-semibold dark:bg-emerald-900/30 dark:text-emerald-300' : 'text-stone-600 dark:text-stone-300 hover:bg-stone-50/50 dark:hover:bg-stone-700/50' }}">
                                        <x-icon name="{{ $item['icon'] }}" class="w-4 h-4 text-stone-500" /> {{ $item['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <a href="{{ route($entrada['route']) }}"
                           class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-semibold transition {{ $activo ? $claseActiva($entrada) : $claseInactiva }}">
                            <x-icon name="{{ $entrada['icon'] }}" class="w-4 h-4" /> {{ $entrada['label'] }}
                        </a>
                    @endif
                @endforeach
            </div>

            <!-- Notificaciones + Tema + Usuario (escritorio) -->
            <div class="hidden md:flex items-center gap-2">

                <x-tasa-badge :tasa="$tasaActual ?? null" />

                <button type="button" onclick="alternarTema()" class="p-2.5 rounded-lg hover:bg-stone-50/50 dark:hover:bg-stone-800/50 transition text-stone-500 dark:text-stone-400" title="Cambiar tema claro/oscuro">
                    <x-icon name="sun" class="w-5 h-5 dark:hidden" />
                    <x-icon name="moon" class="w-5 h-5 hidden dark:block" />
                </button>

                <div class="relative" data-dropdown>
                    <button type="button" data-dropdown-toggle class="relative p-2.5 rounded-lg hover:bg-stone-50/50 dark:hover:bg-stone-800/50 transition text-stone-500 dark:text-stone-400">
                        <x-icon name="bell" class="w-5 h-5" />
                        @if($notificaciones->count() > 0)
                            <span class="absolute top-1 right-1 w-4 h-4 bg-rose-600 text-white text-[10px] font-bold rounded-full flex items-center justify-center">
                                {{ $notificaciones->count() }}
                            </span>
                        @endif
                    </button>
                    <div data-dropdown-menu class="hidden absolute right-0 mt-2 w-80 bg-white dark:bg-stone-800 rounded-xl shadow-lg border border-stone-200 dark:border-stone-700 ring-1 ring-stone-200 dark:ring-stone-700 py-1.5 overflow-hidden">
                        <div class="px-4 py-2.5 border-b border-stone-200 dark:border-stone-700">
                            <p class="text-sm font-bold text-stone-800 dark:text-stone-100">Notificaciones</p>
                        </div>
                        <div class="max-h-80 overflow-y-auto">
                            @forelse($notificaciones as $n)
                                <a href="{{ $n['url'] }}" class="flex items-start gap-2.5 px-4 py-3 text-sm text-stone-600 dark:text-stone-300 hover:bg-stone-50/50 dark:hover:bg-stone-700/50 border-b border-stone-300 dark:border-stone-700">
                                    <x-icon name="{{ $n['icono'] }}" class="w-4 h-4 shrink-0 mt-0.5 {{ match($n['color']) { 'rose' => 'text-rose-500', 'amber' => 'text-amber-500', 'blue' => 'text-blue-500', default => 'text-stone-500' } }}" />
                                    <span>{{ $n['texto'] }}</span>
                                </a>
                            @empty
                                <p class="px-4 py-6 text-center text-sm text-stone-500 dark:text-stone-400 flex items-center justify-center gap-1.5">
                                    <x-icon name="check-circle" class="w-4 h-4 text-emerald-500" /> Todo en orden
                                </p>
                            @endforelse
                        </div>
                        @if(auth()->user()->esEncargadoOSuperior())
                            <a href="{{ route('inventario.alertas') }}" class="block px-4 py-2.5 text-xs font-bold text-emerald-800 dark:text-emerald-400 text-center border-t border-stone-200 dark:border-stone-700 hover:bg-stone-50/50 dark:hover:bg-stone-700/50">
                                Ver todas las alertas de inventario →
                            </a>
                        @endif
                    </div>
                </div>

                <div class="relative" data-dropdown>
                <button type="button" data-dropdown-toggle class="flex items-center gap-2 pl-1.5 pr-2.5 py-1.5 rounded-lg hover:bg-stone-50/50 dark:hover:bg-stone-800/50 transition">
                    @if(auth()->user()->fotoUrl())
                        <img src="{{ auth()->user()->fotoUrl() }}" alt="{{ auth()->user()->name }}" class="w-7 h-7 rounded-full object-cover">
                    @else
                        <span class="w-7 h-7 rounded-full bg-emerald-700 text-white font-bold text-xs flex items-center justify-center">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </span>
                    @endif
                    <span class="text-stone-800 dark:text-stone-100 text-sm font-semibold">{{ auth()->user()->name }}</span>
                    <x-icon name="chevron-down" class="w-3.5 h-3.5 text-stone-500 dark:text-stone-400" />
                </button>
                <div data-dropdown-menu class="hidden absolute right-0 mt-2 w-52 bg-white dark:bg-stone-800 rounded-xl shadow-lg border border-stone-200 dark:border-stone-700 ring-1 ring-stone-200 dark:ring-stone-700 py-1.5 overflow-hidden">
                    <div class="px-4 py-2.5 border-b border-stone-200 dark:border-stone-700">
                        <p class="text-[11px] text-stone-500 dark:text-stone-400 uppercase tracking-wide">Sesión iniciada como</p>
                        <p class="text-sm font-bold text-stone-800 dark:text-stone-100">{{ auth()->user()->rol }}</p>
                    </div>
                    <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-stone-600 dark:text-stone-300 hover:bg-stone-50/50 dark:hover:bg-stone-700/50">
                        <x-icon name="user-circle" class="w-4 h-4 text-stone-500" /> Editar mi perfil
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

            <!-- Botón menú móvil -->
            <button type="button" data-mobile-toggle class="md:hidden text-stone-600 dark:text-stone-300 p-2">
                <x-icon name="menu" class="w-6 h-6" />
            </button>
        </div>

        <!-- Menú móvil (mismo $menu que escritorio) -->
        <div data-mobile-menu class="hidden md:hidden pb-4 space-y-1 border-t border-stone-200 dark:border-stone-700 pt-3">
            @foreach($menu as $entrada)
                @if(isset($entrada['items']))
                    <p class="px-3 pt-3 pb-1 text-[11px] font-bold uppercase tracking-wide text-stone-400 dark:text-stone-500">{{ $entrada['label'] }}</p>
                    @foreach($entrada['items'] as $item)
                        <a href="{{ route($item['route']) }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-semibold hover:bg-stone-50/50 dark:hover:bg-stone-800/50 {{ request()->routeIs($item['match']) ? 'text-emerald-800 dark:text-emerald-300' : 'text-stone-600 dark:text-stone-300' }}">
                            <x-icon name="{{ $item['icon'] }}" class="w-4 h-4 text-stone-500" /> {{ $item['label'] }}
                        </a>
                    @endforeach
                @else
                    <a href="{{ route($entrada['route']) }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-semibold hover:bg-stone-50/50 dark:hover:bg-stone-800/50 {{ !empty($entrada['violeta']) ? 'text-violet-800 dark:text-violet-300' : 'text-stone-600 dark:text-stone-300' }}">
                        <x-icon name="{{ $entrada['icon'] }}" class="w-4 h-4 {{ !empty($entrada['violeta']) ? '' : 'text-stone-500' }}" /> {{ $entrada['label'] }}
                    </a>
                @endif
            @endforeach

            <div class="border-t border-stone-200 dark:border-stone-700 mt-2 pt-2 space-y-1">
                <div class="px-3 py-2"><x-tasa-badge :tasa="$tasaActual ?? null" /></div>
                <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-stone-600 dark:text-stone-300 hover:bg-stone-50/50 dark:hover:bg-stone-800/50 text-sm font-semibold">
                    <x-icon name="user-circle" class="w-4 h-4 text-stone-500" /> {{ $usuarioNav->name }} ({{ $usuarioNav->rol }})
                </a>
                <button type="button" onclick="alternarTema()" class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-stone-600 dark:text-stone-300 hover:bg-stone-50/50 dark:hover:bg-stone-800/50 text-sm font-semibold">
                    <x-icon name="sun" class="w-4 h-4 text-stone-500 dark:hidden" />
                    <x-icon name="moon" class="w-4 h-4 text-stone-500 hidden dark:block" />
                    Cambiar tema
                </button>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/20 text-sm font-semibold">
                        <x-icon name="logout" class="w-4 h-4" /> Cerrar sesión
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>

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

    var mobileToggle = document.querySelector('[data-mobile-toggle]');
    var mobileMenu = document.querySelector('[data-mobile-menu]');
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function () {
            mobileMenu.classList.toggle('hidden');
        });
    }
</script>
