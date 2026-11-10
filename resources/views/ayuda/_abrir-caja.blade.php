<div class="space-y-4">
    <div data-zona="estado" class="flex items-center justify-between gap-3">
        <p class="titulo-tarjeta">Caja de hoy</p>
        <span data-salida="estado-caja"></span>
    </div>
    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label for="guia-abrir-usd" class="etiqueta">Fondo inicial en $</label>
            <input id="guia-abrir-usd" data-campo="fondoUsd" class="campo" inputmode="decimal" autocomplete="off" placeholder="0.00">
            <p class="ayuda-campo">Efectivo en dólares con el que empiezas.</p>
        </div>
        <div>
            <label for="guia-abrir-ves" class="etiqueta">Fondo inicial en Bs.</label>
            <input id="guia-abrir-ves" data-campo="fondoVes" class="campo" inputmode="decimal" autocomplete="off" placeholder="0,00">
            <p class="ayuda-campo">Efectivo en bolívares. Si no tienes, déjalo vacío.</p>
        </div>
    </div>
    <div class="flex justify-end">
        <button type="button" data-accion="abrir" class="btn-primario"><x-icon name="banknote" class="w-4 h-4" /> Abrir caja del día</button>
    </div>
    <div data-mensaje data-zona="mensaje" hidden></div>
</div>
