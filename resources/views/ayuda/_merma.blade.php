<div class="space-y-4">
    <p class="titulo-tarjeta">Registrar merma</p>
    <div class="grid sm:grid-cols-3 gap-4">
        <div>
            <label for="guia-merma-producto" class="etiqueta">Producto</label>
            <select id="guia-merma-producto" data-campo="producto" class="campo">
                <option value="">Elige el producto…</option>
                <option value="tomate">Tomate (kg)</option>
                <option value="aguacate">Aguacate (unidad)</option>
            </select>
        </div>
        <div>
            <label for="guia-merma-cantidad" class="etiqueta">Cantidad perdida</label>
            <input id="guia-merma-cantidad" data-campo="cantidad" class="campo" inputmode="decimal" autocomplete="off">
            <p class="ayuda-campo" data-salida="ayuda-cantidad"></p>
        </div>
        <div>
            <label for="guia-merma-motivo" class="etiqueta">Motivo</label>
            <select id="guia-merma-motivo" data-campo="motivo" class="campo">
                <option value="">Elige el motivo…</option>
                <option value="Se dañó">Se dañó</option>
                <option value="Se venció">Se venció</option>
                <option value="Se perdió">Se perdió</option>
            </select>
        </div>
    </div>
    <div class="flex justify-end">
        <button type="button" data-accion="registrar" class="btn-primario"><x-icon name="package-x" class="w-4 h-4" /> Registrar merma</button>
    </div>
    <div data-mensaje data-zona="mensaje" hidden></div>
</div>
