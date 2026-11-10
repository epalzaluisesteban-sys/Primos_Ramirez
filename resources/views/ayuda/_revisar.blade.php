<div class="space-y-4">
    <div data-zona="kardex" class="tarjeta p-0 overflow-hidden">
        <p class="titulo-tarjeta px-4 py-3 border-b border-stone-200 dark:border-stone-700">Kardex del tomate</p>
        <div class="overflow-x-auto">
            <table class="tabla">
                <thead><tr><th>Movimiento</th><th class="text-right">Entra / sale</th><th class="text-right">Queda</th></tr></thead>
                <tbody data-salida="kardex"></tbody>
            </table>
        </div>
    </div>

    <div data-zona="ganancia" class="tarjeta p-0 overflow-hidden">
        <p class="titulo-tarjeta px-4 py-3 border-b border-stone-200 dark:border-stone-700">Ganancia del día</p>
        <div class="overflow-x-auto">
            <table class="tabla">
                <thead><tr><th>Producto</th><th class="text-right">Vendido</th><th class="text-right">Costo</th><th class="text-right">Ganancia</th></tr></thead>
                <tbody data-salida="ganancia"></tbody>
            </table>
        </div>
    </div>

    <div data-zona="sin-costo" class="aviso-ambar">
        <x-icon name="alert-triangle" class="w-4 h-4 shrink-0 mt-0.5" />
        <p data-salida="sin-costo"></p>
    </div>

    <div data-zona="lugares" class="caja-suave p-4 text-sm text-stone-700 dark:text-stone-200 space-y-1.5">
        <p class="titulo-seccion mb-2">Dónde está cada cosa</p>
        <p><strong>Reportes</strong>: ganancias por fecha y estadísticas de ventas.</p>
        <p><strong>Productos → Kardex</strong>: cada entrada y salida de un producto.</p>
        <p><strong>Historial</strong>: todo lo que se hizo en el sistema y quién lo hizo.</p>
        <p><strong>Administración → Respaldos</strong> (solo el Dueño): bajar una copia de toda la información.</p>
    </div>
</div>
