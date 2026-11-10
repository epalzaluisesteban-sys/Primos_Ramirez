<div class="space-y-4">
    <div data-zona="deuda" class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="titulo-tarjeta">Cliente de ejemplo</p>
            <p class="text-xs text-stone-500 dark:text-stone-400">Límite de crédito: $30.00</p>
        </div>
        <div class="text-right">
            <p class="cifra-etiqueta">Debe</p>
            <p class="font-display text-xl font-bold text-orange-700 dark:text-orange-400" data-salida="deuda"></p>
        </div>
    </div>
    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label for="guia-abono-moneda" class="etiqueta">Paga en</label>
            <select id="guia-abono-moneda" data-campo="moneda" class="campo">
                <option value="USD">Dólares</option>
                <option value="VES">Bolívares</option>
            </select>
        </div>
        <div>
            <label for="guia-abono-monto" class="etiqueta">Monto del abono</label>
            <input id="guia-abono-monto" data-campo="monto" class="campo" inputmode="decimal" autocomplete="off">
            <p class="ayuda-campo" data-salida="equivale" data-zona="equivale"></p>
        </div>
    </div>
    <div class="flex justify-end">
        <button type="button" data-accion="registrar" class="btn-primario"><x-icon name="banknote" class="w-4 h-4" /> Registrar abono</button>
    </div>
    <div data-mensaje data-zona="mensaje" hidden></div>
</div>
