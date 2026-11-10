{{-- Formulario de venta de práctica, usado por los capítulos "contado" y "fiado" ($prefijo evita ids repetidos) --}}
<div class="space-y-4">
    <p class="titulo-tarjeta">Nueva venta</p>
    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label for="guia-{{ $prefijo }}-tipo" class="etiqueta">Tipo de pago</label>
            <select id="guia-{{ $prefijo }}-tipo" data-campo="tipoPago" class="campo">
                <option value="contado">De contado</option>
                <option value="credito">Fiado</option>
            </select>
        </div>
        <div data-solo="contado">
            <label for="guia-{{ $prefijo }}-moneda" class="etiqueta">Te pagan en</label>
            <select id="guia-{{ $prefijo }}-moneda" data-campo="moneda" class="campo">
                <option value="USD">Dólares</option>
                <option value="VES">Bolívares</option>
            </select>
        </div>
        <div data-solo="credito" hidden>
            <label for="guia-{{ $prefijo }}-cliente" class="etiqueta">Cliente</label>
            <select id="guia-{{ $prefijo }}-cliente" data-campo="cliente" class="campo">
                <option value="">Elige el cliente…</option>
                <option value="ejemplo">Cliente de ejemplo</option>
            </select>
            <p class="ayuda-campo" data-salida="credito" data-zona="credito"></p>
        </div>
        <div>
            <label for="guia-{{ $prefijo }}-producto" class="etiqueta">Producto</label>
            <select id="guia-{{ $prefijo }}-producto" data-campo="producto" class="campo">
                <option value="">Elige el producto…</option>
                <option value="tomate">Tomate · $2.00 el kg</option>
                <option value="aguacate">Aguacate · $0.80 la unidad</option>
            </select>
        </div>
        <div>
            <label for="guia-{{ $prefijo }}-cantidad" class="etiqueta">Cantidad</label>
            <input id="guia-{{ $prefijo }}-cantidad" data-campo="cantidad" class="campo" inputmode="decimal" autocomplete="off">
            <p class="ayuda-campo" data-salida="ayuda-cantidad"></p>
        </div>
    </div>
    <div data-zona="total" class="caja-suave px-4 py-3 flex items-center justify-between gap-3">
        <span class="cifra-etiqueta">Total</span>
        <span class="font-display text-lg font-bold text-stone-900 dark:text-stone-100 text-right" data-salida="total"></span>
    </div>
    <div class="flex justify-end">
        <button type="button" data-accion="registrar" class="btn-primario"><x-icon name="cart" class="w-4 h-4" /> Registrar venta</button>
    </div>
    <div data-mensaje data-zona="mensaje" hidden></div>
</div>
