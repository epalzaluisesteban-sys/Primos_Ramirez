<div class="space-y-4">
    <div class="flex items-center justify-between gap-3">
        <p class="titulo-tarjeta">Cerrar la caja de hoy</p>
        <span data-salida="estado-caja"></span>
    </div>
    <div data-zona="resumen" class="caja-suave overflow-x-auto">
        <table class="tabla">
            <thead><tr><th>Concepto</th><th class="text-right">Dólares</th><th class="text-right">Bolívares</th></tr></thead>
            <tbody>
                <tr><td>Fondo inicial</td><td class="text-right whitespace-nowrap" data-salida="fondo-usd"></td><td class="text-right whitespace-nowrap" data-salida="fondo-ves"></td></tr>
                <tr><td>Ventas de contado</td><td class="text-right whitespace-nowrap" data-salida="contado-usd"></td><td class="text-right whitespace-nowrap" data-salida="contado-ves"></td></tr>
                <tr><td>Abonos de clientes</td><td class="text-right whitespace-nowrap" data-salida="abonos-usd"></td><td class="text-right whitespace-nowrap" data-salida="abonos-ves"></td></tr>
                <tr class="font-bold text-stone-900 dark:text-stone-100"><td>Debería haber</td><td class="text-right whitespace-nowrap" data-salida="esperado-usd"></td><td class="text-right whitespace-nowrap" data-salida="esperado-ves"></td></tr>
            </tbody>
        </table>
    </div>
    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label for="guia-cierre-usd" class="etiqueta">Efectivo contado en $</label>
            <input id="guia-cierre-usd" data-campo="realUsd" class="campo" inputmode="decimal" autocomplete="off">
        </div>
        <div>
            <label for="guia-cierre-ves" class="etiqueta">Efectivo contado en Bs.</label>
            <input id="guia-cierre-ves" data-campo="realVes" class="campo" inputmode="decimal" autocomplete="off">
        </div>
        <div class="sm:col-span-2">
            <label for="guia-cierre-obs" class="etiqueta">Observaciones</label>
            <input id="guia-cierre-obs" data-campo="observaciones" class="campo" autocomplete="off" placeholder="Si sobra o falta, explica por qué">
        </div>
    </div>
    <div class="flex justify-end">
        <button type="button" data-accion="cerrar" class="btn-oscuro"><x-icon name="lock" class="w-4 h-4" /> Cerrar caja</button>
    </div>
    <div data-mensaje data-zona="mensaje" hidden></div>
</div>
