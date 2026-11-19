<div class="space-y-4">
    <p class="titulo-tarjeta">Registrar compra</p>
    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label for="guia-compra-producto" class="etiqueta">Producto</label>
            <select id="guia-compra-producto" data-campo="producto" class="campo">
                <option value="">Elige el producto…</option>
                <option value="tomate">Tomate (kg)</option>
                <option value="aguacate">Aguacate (unidad)</option>
            </select>
            <p class="ayuda-campo" data-salida="ayuda-cantidad"></p>
        </div>
        <div>
            <label for="guia-compra-proveedor" class="etiqueta">Proveedor</label>
            <select id="guia-compra-proveedor" data-campo="proveedor" class="campo">
                <option value="">Elige el proveedor…</option>
                <option value="ejemplo">Proveedor de ejemplo — Frutas, Verduras (da crédito a 15 días)</option>
            </select>
        </div>
        <div class="sm:col-span-2">
            <label for="guia-compra-pago" class="etiqueta">Forma de pago</label>
            <select id="guia-compra-pago" data-campo="pago" class="campo">
                <option value="">Elige…</option>
                <option value="contado">De contado</option>
                <option value="credito">A crédito (se le paga después)</option>
            </select>
        </div>
        <div>
            <label for="guia-compra-cantidad" class="etiqueta">Cantidad</label>
            <input id="guia-compra-cantidad" data-campo="cantidad" class="campo" inputmode="decimal" autocomplete="off">
        </div>
        <div>
            <label for="guia-compra-precio" class="etiqueta">Precio de compra por unidad ($)</label>
            <input id="guia-compra-precio" data-campo="precio" class="campo" inputmode="decimal" autocomplete="off" placeholder="0.00">
        </div>
    </div>
    <div class="grid sm:grid-cols-2 gap-3">
        <div data-zona="costo" class="caja-suave px-4 py-3 text-sm text-stone-700 dark:text-stone-200">
            Costo promedio del tomate: <strong data-salida="costo"></strong>
        </div>
        <div data-zona="deuda" class="caja-suave px-4 py-3 text-sm text-stone-700 dark:text-stone-200">
            Le debes al proveedor: <strong data-salida="deuda"></strong>
        </div>
    </div>
    <div class="flex justify-end">
        <button type="button" data-accion="registrar" class="btn-primario"><x-icon name="truck" class="w-4 h-4" /> Registrar compra</button>
    </div>
    <div data-mensaje data-zona="mensaje" hidden></div>
</div>
