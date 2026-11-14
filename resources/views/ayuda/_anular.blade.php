<div class="space-y-4">
    <div data-zona="ventas" class="tarjeta p-0 overflow-hidden">
        <p class="titulo-tarjeta px-4 py-3 border-b border-stone-200 dark:border-stone-700">Ventas de hoy</p>
        <div class="overflow-x-auto">
            <table class="tabla">
                <thead>
                    <tr><th>N°</th><th>Productos</th><th>Pago</th><th class="text-right">Total</th><th class="text-center">Estado</th></tr>
                </thead>
                <tbody data-salida="ventas"></tbody>
            </table>
        </div>
    </div>
    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label for="guia-anular-venta" class="etiqueta">Venta a eliminar</label>
            <select id="guia-anular-venta" data-campo="venta" class="campo"></select>
        </div>
        <div>
            <label for="guia-anular-motivo" class="etiqueta">Motivo</label>
            <input id="guia-anular-motivo" data-campo="motivo" class="campo" autocomplete="off" placeholder="Ej. se registró mal la cantidad">
        </div>
    </div>
    <div class="flex justify-end">
        <button type="button" data-accion="anular" class="btn-peligro"><x-icon name="trash" class="w-4 h-4" /> Eliminar venta</button>
    </div>
    <div data-mensaje data-zona="mensaje" hidden></div>
</div>
