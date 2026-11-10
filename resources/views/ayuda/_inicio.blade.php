<div class="space-y-4">
    <div class="caja-suave flex flex-wrap items-center gap-x-4 gap-y-2 px-3 py-2.5">
        <span class="font-display font-extrabold text-sm text-stone-900 dark:text-stone-100">Primos Ramírez</span>
        <span data-zona="menu" class="flex flex-wrap gap-x-3 gap-y-1 text-xs font-semibold text-stone-600 dark:text-stone-300">
            <span>Inicio</span><span>Ventas</span><span>Caja</span><span>Productos</span><span>Clientes</span><span>Reportes</span>
        </span>
        <span class="ml-auto flex items-center gap-3">
            <span data-zona="tasa" class="insignia-verde">BCV <span data-salida="tasa"></span></span>
            <span data-zona="campana" class="relative text-stone-600 dark:text-stone-300">
                <x-icon name="bell" class="w-5 h-5" />
                <span class="absolute -top-1 -right-1.5 w-4 h-4 rounded-full bg-rose-600 text-white text-[10px] font-bold flex items-center justify-center">2</span>
            </span>
            <span data-zona="avatar" class="w-7 h-7 rounded-full bg-emerald-700 text-white font-bold text-xs flex items-center justify-center">
                {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
            </span>
        </span>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div data-zona="avisos" class="menu-desplegable text-sm">
            <p class="px-4 py-2 text-[11px] font-bold uppercase tracking-wide text-stone-500 dark:text-stone-400 border-b border-stone-200 dark:border-stone-700">Avisos de la campana</p>
            <p class="flex items-start gap-2.5 px-4 py-2.5 text-stone-600 dark:text-stone-300 border-b border-stone-200 dark:border-stone-700">
                <x-icon name="alert-triangle" class="w-4 h-4 shrink-0 mt-0.5 text-amber-500" /> Aguacate: quedan 4 unidades
            </p>
            <p class="flex items-start gap-2.5 px-4 py-2.5 text-stone-600 dark:text-stone-300">
                <x-icon name="clock" class="w-4 h-4 shrink-0 mt-0.5 text-rose-500" /> Tomate: vence en 2 días
            </p>
        </div>
        <div data-zona="menu-avatar" class="menu-desplegable text-sm">
            <p class="px-4 py-2 text-[11px] font-bold uppercase tracking-wide text-stone-500 dark:text-stone-400 border-b border-stone-200 dark:border-stone-700">Menú de tu inicial</p>
            <p class="flex items-center gap-2.5 px-4 py-2.5 text-stone-600 dark:text-stone-300"><x-icon name="user-circle" class="w-4 h-4 text-stone-500" /> Editar mi perfil</p>
            <p class="flex items-center gap-2.5 px-4 py-2.5 text-stone-600 dark:text-stone-300"><x-icon name="ayuda" class="w-4 h-4 text-stone-500" /> Cómo usar el sistema</p>
            <p class="flex items-center gap-2.5 px-4 py-2.5 text-rose-600 dark:text-rose-400 font-semibold"><x-icon name="logout" class="w-4 h-4" /> Cerrar sesión</p>
        </div>
    </div>

    <div data-zona="roles" class="overflow-x-auto">
        <table class="tabla">
            <thead><tr><th>Rol</th><th>Qué hace</th></tr></thead>
            <tbody>
                <tr><td class="font-semibold whitespace-nowrap">Obrero</td><td>Abre y cierra la caja, registra ventas y ve sus ventas.</td></tr>
                <tr><td class="font-semibold whitespace-nowrap">Encargado</td><td>Lo del obrero y además compras, abonos, mermas, productos, clientes, proveedores y reportes. Puede corregir o anular ventas del día y reabrir la caja.</td></tr>
                <tr><td class="font-semibold whitespace-nowrap">Dueño</td><td>Todo: además maneja el personal, puede eliminar productos, clientes o proveedores y hace los respaldos.</td></tr>
            </tbody>
        </table>
    </div>
</div>
