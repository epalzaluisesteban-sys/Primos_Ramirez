{{-- Formulario compartido entre crear y editar producto. Cada campo tiene su regla en ProductoController::validacionProducto(). --}}
@php
    $error = fn (string $campo) => $errors->has($campo) ? 'border-rose-500' : '';
@endphp
<div class="flex items-center gap-4">
    <div class="shrink-0">
        @if(isset($producto) && $producto->fotoUrl())
            <img src="{{ $producto->fotoUrl() }}" alt="{{ $producto->nombre }}" class="w-16 h-16 rounded-md object-cover border border-stone-300 dark:border-stone-700">
        @else
            <span class="w-16 h-16 rounded-md bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 flex items-center justify-center border border-stone-300 dark:border-stone-700">
                <x-icon name="box" class="w-6 h-6" />
            </span>
        @endif
    </div>
    <div class="flex-1">
        <label class="etiqueta" for="foto">Fotografía (Opcional)</label>
        <input type="file" id="foto" name="foto" accept="image/*" class="campo-archivo">
        <p class="ayuda-campo">JPG o PNG, máximo 2MB.</p>
        @error('foto') <p class="error-campo">{{ $message }}</p> @enderror
    </div>
</div>

<div>
    <label class="etiqueta" for="nombre">Nombre del producto</label>
    <input type="text" id="nombre" name="nombre" value="{{ old('nombre', $producto->nombre ?? '') }}" required minlength="2" maxlength="255" placeholder="Ej. Cambur" class="campo {{ $error('nombre') }}">
    <p class="ayuda-campo">No se puede repetir con la misma marca y unidad de medida.</p>
    @error('nombre') <p class="error-campo">{{ $message }}</p> @enderror
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="etiqueta" for="marca">Marca (Opcional)</label>
        <input type="text" id="marca" name="marca" value="{{ old('marca', $producto->marca ?? '') }}" maxlength="255" placeholder="Ej. Polar, Diana…" class="campo {{ $error('marca') }}">
        @error('marca') <p class="error-campo">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="etiqueta" for="categoria_id">Categoría</label>
        <select id="categoria_id" name="categoria_id" required class="campo {{ $error('categoria_id') }}">
            <option value="">Selecciona…</option>
            @foreach($categorias as $cat)
                <option value="{{ $cat->id }}" @selected(old('categoria_id', $producto->categoria_id ?? '') == $cat->id)>{{ $cat->nombre }}</option>
            @endforeach
        </select>
        @error('categoria_id') <p class="error-campo">{{ $message }}</p> @enderror
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="etiqueta" for="ubicacion">Ubicación (Opcional)</label>
        <input type="text" id="ubicacion" name="ubicacion" value="{{ old('ubicacion', $producto->ubicacion ?? '') }}" maxlength="255" placeholder="Ej. Pasillo 3, Anaquel B" class="campo {{ $error('ubicacion') }}">
        @error('ubicacion') <p class="error-campo">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="etiqueta" for="unidad_medida">Unidad de medida</label>
        <select name="unidad_medida" id="unidad_medida" required onchange="ajustarExistencias()" class="campo {{ $error('unidad_medida') }}">
            @foreach(\App\Models\Producto::UNIDADES as $key => $label)
                <option value="{{ $key }}" data-entero="{{ \App\Models\Producto::unidadEsEntera($key) ? 1 : 0 }}" data-ayuda="{{ \App\Models\Producto::ayudaParaUnidad($key) }}" @selected(old('unidad_medida', $producto->unidad_medida ?? 'unidad') == $key)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('unidad_medida') <p class="error-campo">{{ $message }}</p> @enderror
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="etiqueta" for="codigo_barras">Código de barras (Opcional)</label>
        <input type="text" id="codigo_barras" name="codigo_barras" value="{{ old('codigo_barras', $producto->codigo_barras ?? '') }}" maxlength="100" placeholder="Escanea o escribe el código" class="campo {{ $error('codigo_barras') }}">
        @error('codigo_barras') <p class="error-campo">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="etiqueta" for="codigo_qr">Código QR (Opcional)</label>
        <input type="text" id="codigo_qr" name="codigo_qr" value="{{ old('codigo_qr', $producto->codigo_qr ?? '') }}" maxlength="255" placeholder="Vacío: se genera solo" class="campo {{ $error('codigo_qr') }}">
        @error('codigo_qr') <p class="error-campo">{{ $message }}</p> @enderror
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="etiqueta" for="input-precio-compra">Precio de compra ($, Opcional)</label>
        <input type="number" step="0.01" min="0" max="99999999" inputmode="decimal" id="input-precio-compra" name="precio_compra" value="{{ old('precio_compra', $producto->precio_compra ?? '') }}" placeholder="Costo de referencia" class="campo {{ $error('precio_compra') }}">
        @error('precio_compra') <p class="error-campo">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="etiqueta" for="input-precio-venta">Precio de venta ($) <span id="hint-bs" class="normal-case font-medium text-stone-400 dark:text-stone-500" data-tasa="{{ \App\Support\Moneda::tasa() }}"></span></label>
        <input type="number" step="0.01" min="0.01" max="99999999" inputmode="decimal" id="input-precio-venta" name="precio" value="{{ old('precio', $producto->precio ?? '') }}" required placeholder="0.00" class="campo {{ $error('precio') }}">
        @error('precio') <p class="error-campo">{{ $message }}</p> @enderror
    </div>
</div>
<p class="text-xs font-semibold -mt-2">
    Margen de ganancia: <span id="texto-margen" class="text-emerald-700 dark:text-emerald-400">—</span>
</p>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="etiqueta" for="stock">Existencia {{ isset($producto) ? 'actual' : 'inicial' }}</label>
        <input type="number" id="stock" min="0" max="9999999" step="0.001" name="stock" data-existencia value="{{ old('stock', isset($producto) ? \App\Support\Cantidad::formato($producto->stock) : 0) }}" required class="campo {{ $error('stock') }}">
        <p id="ayuda-existencias" class="ayuda-campo">{{ \App\Models\Producto::ayudaParaUnidad(old('unidad_medida', $producto->unidad_medida ?? 'unidad')) }} Aplica a todas las existencias y cantidades del producto.</p>
        @error('stock') <p class="error-campo">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="etiqueta" for="fecha_vencimiento">Fecha de vencimiento (Opcional)</label>
        <input type="date" id="fecha_vencimiento" name="fecha_vencimiento" @unless(isset($producto)) min="{{ today()->format('Y-m-d') }}" @endunless
               value="{{ old('fecha_vencimiento', isset($producto->fecha_vencimiento) ? $producto->fecha_vencimiento?->format('Y-m-d') : '') }}" class="campo {{ $error('fecha_vencimiento') }}">
        <p class="ayuda-campo">Del lote actual en anaquel. Útil para perecederos.</p>
        @error('fecha_vencimiento') <p class="error-campo">{{ $message }}</p> @enderror
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="etiqueta" for="stock_minimo">Existencia mínima (alerta)</label>
        <input type="number" id="stock_minimo" min="0" max="9999999" step="0.001" name="stock_minimo" data-existencia value="{{ old('stock_minimo', isset($producto) ? \App\Support\Cantidad::formato($producto->stock_minimo) : 5) }}" required class="campo {{ $error('stock_minimo') }}">
        <p class="ayuda-campo">Se avisará cuando la existencia baje a este nivel o menos.</p>
        @error('stock_minimo') <p class="error-campo">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="etiqueta" for="stock_maximo">Existencia máxima (Opcional)</label>
        <input type="number" id="stock_maximo" min="0" max="9999999" step="0.001" name="stock_maximo" data-existencia value="{{ old('stock_maximo', isset($producto) && $producto->stock_maximo !== null ? \App\Support\Cantidad::formato($producto->stock_maximo) : '') }}" class="campo {{ $error('stock_maximo') }}">
        <p class="ayuda-campo">Referencia para planificar compras. No puede ser menor que la mínima.</p>
        @error('stock_maximo') <p class="error-campo">{{ $message }}</p> @enderror
    </div>
</div>

@if(isset($producto))
    <div>
        <label class="etiqueta" for="motivo_ajuste">Motivo del ajuste de existencia (si la cambias)</label>
        <input type="text" id="motivo_ajuste" name="motivo_ajuste" maxlength="255" value="{{ old('motivo_ajuste') }}" placeholder="Ej. Conteo físico del sábado" class="campo {{ $error('motivo_ajuste') }}">
        <p class="ayuda-campo">Para entradas de mercancía usa <strong>Recibir mercancía</strong> y para pérdidas <strong>Registrar pérdida</strong>. Si cambias la existencia aquí, queda como ajuste en el kardex.</p>
        @error('motivo_ajuste') <p class="error-campo">{{ $message }}</p> @enderror
    </div>

    <label class="caja-suave flex items-start gap-3 p-3 cursor-pointer">
        <input type="hidden" name="activo" value="0">
        <input type="checkbox" name="activo" value="1" @checked(old('activo', $producto->activo)) class="casilla mt-0.5">
        <span>
            <span class="block text-sm font-semibold text-stone-700 dark:text-stone-200">Producto activo</span>
            <span class="block text-[11px] text-stone-500 dark:text-stone-400">Desmárcalo para dejar de venderlo y comprarlo sin perder su historial.</span>
        </span>
    </label>
@endif

<script>
    (function () {
        var compra = document.getElementById('input-precio-compra');
        var venta = document.getElementById('input-precio-venta');
        var texto = document.getElementById('texto-margen');

        function actualizarMargen() {
            var c = parseFloat(compra.value);
            var v = parseFloat(venta.value);
            if (!c || c <= 0 || isNaN(v)) {
                texto.textContent = '—';
                return;
            }
            var margen = ((v - c) / c) * 100;
            texto.textContent = margen.toFixed(1) + '%';
            texto.className = margen >= 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400';
        }

        compra.addEventListener('input', actualizarMargen);
        venta.addEventListener('input', actualizarMargen);
        actualizarMargen();
    })();
</script>

<script>
    (function () {
        const input = document.getElementById('input-precio-venta');
        const hint = document.getElementById('hint-bs');
        const tasa = parseFloat(hint.dataset.tasa) || 0;
        function actualizar() {
            const v = parseFloat(input.value) || 0;
            hint.textContent = tasa > 0 && v > 0 ? '= Bs. ' + (v * tasa).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '';
        }
        input.addEventListener('input', actualizar);
        actualizar();
    })();
</script>

<script>
    // Existencias sin decimales cuando el producto se cuenta por unidad, caja, atado o saco
    function ajustarExistencias() {
        const unidad = document.getElementById('unidad_medida');
        const entera = unidad.options[unidad.selectedIndex].dataset.entero === '1';
        const ayuda = document.getElementById('ayuda-existencias');
        if (ayuda) {
            ayuda.textContent = unidad.options[unidad.selectedIndex].dataset.ayuda + ' Aplica a todas las existencias y cantidades del producto.';
        }
        document.querySelectorAll('[data-existencia]').forEach(function (campo) {
            campo.step = entera ? '1' : '0.001';
            campo.inputMode = entera ? 'numeric' : 'decimal';
            campo.dataset.entero = entera ? '1' : '0';
        });
    }
    ajustarExistencias();
</script>
