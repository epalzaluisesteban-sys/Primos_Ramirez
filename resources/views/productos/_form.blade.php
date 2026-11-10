{{-- Formulario compartido entre crear y editar producto --}}
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
        <label class="etiqueta">Fotografía (Opcional)</label>
        <input type="file" name="foto" accept="image/*" class="campo-archivo">
        <p class="text-[11px] text-stone-400 dark:text-stone-500 mt-1">JPG o PNG, máximo 2MB.</p>
    </div>
</div>

<div>
    <label class="etiqueta">Nombre del producto</label>
    <input type="text" name="nombre" value="{{ old('nombre', $producto->nombre ?? '') }}" required placeholder="Ej. Cambur (Kg)" class="campo">
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="etiqueta">Marca (Opcional)</label>
        <input type="text" name="marca" value="{{ old('marca', $producto->marca ?? '') }}" placeholder="Ej. Polar, Diana..." class="campo">
    </div>
    <div>
        <label class="etiqueta">Categoría</label>
        <select name="categoria_id" required class="campo">
            <option value="">-- Seleccionar categoría --</option>
            @foreach($categorias as $cat)
                <option value="{{ $cat->id }}" {{ old('categoria_id', $producto->categoria_id ?? '') == $cat->id ? 'selected' : '' }}>
                    {{ $cat->nombre }}
                </option>
            @endforeach
        </select>
    </div>
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="etiqueta">Ubicación (Opcional)</label>
        <input type="text" name="ubicacion" value="{{ old('ubicacion', $producto->ubicacion ?? '') }}" placeholder="Ej. Pasillo 3, Anaquel B" class="campo">
    </div>
    <div>
        <label class="etiqueta">Unidad de medida</label>
        <select name="unidad_medida" id="unidad_medida" required onchange="ajustarExistencias()" class="campo">
            @foreach(\App\Models\Producto::UNIDADES as $key => $label)
                <option value="{{ $key }}" data-entero="{{ \App\Models\Producto::unidadEsEntera($key) ? 1 : 0 }}" data-ayuda="{{ \App\Models\Producto::ayudaParaUnidad($key) }}" {{ old('unidad_medida', $producto->unidad_medida ?? 'unidad') == $key ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="etiqueta">Código de barras (opcional)</label>
        <input type="text" name="codigo_barras" value="{{ old('codigo_barras', $producto->codigo_barras ?? '') }}" placeholder="Escanea o escribe el código" class="campo">
    </div>
    <div>
        <label class="etiqueta">Código QR (Opcional)</label>
        <input type="text" name="codigo_qr" value="{{ old('codigo_qr', $producto->codigo_qr ?? '') }}" placeholder="Déjalo vacío para generarlo automático" class="campo">
    </div>
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="etiqueta">Precio de compra ($, opcional)</label>
        <input type="number" step="0.01" min="0" id="input-precio-compra" name="precio_compra" value="{{ old('precio_compra', $producto->precio_compra ?? '') }}" placeholder="Costo de referencia" class="campo">
    </div>
    <div>
        <label class="etiqueta">Precio de venta ($) <span id="hint-bs" class="normal-case font-medium text-stone-400 dark:text-stone-500" data-tasa="{{ \App\Support\Moneda::tasa() }}"></span></label>
        <input type="number" step="0.01" min="0" id="input-precio-venta" name="precio" value="{{ old('precio', $producto->precio ?? '') }}" required placeholder="0.00" class="campo">
    </div>
</div>
<p class="text-xs font-semibold -mt-2">
    Margen de ganancia: <span id="texto-margen" class="text-emerald-700 dark:text-emerald-400">—</span>
</p>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="etiqueta">Stock {{ isset($producto) ? 'Actual' : 'Inicial' }}</label>
        <input type="number" min="0" step="0.001" name="stock" data-existencia value="{{ old('stock', $producto->stock ?? 0) }}" required class="campo">
        <p id="ayuda-existencias" class="ayuda-campo">{{ \App\Models\Producto::ayudaParaUnidad(old('unidad_medida', $producto->unidad_medida ?? 'unidad')) }} Aplica a todas las existencias y cantidades del producto.</p>
    </div>
    <div>
        <label class="etiqueta">Fecha de vencimiento (opcional)</label>
        <input type="date" name="fecha_vencimiento" value="{{ old('fecha_vencimiento', isset($producto->fecha_vencimiento) ? $producto->fecha_vencimiento?->format('Y-m-d') : '') }}" class="campo">
        <p class="text-[11px] text-stone-500 dark:text-stone-400 mt-1">Del lote actual en anaquel. Útil para perecederos.</p>
    </div>
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="etiqueta">Stock mínimo (alerta)</label>
        <input type="number" min="0" step="0.001" name="stock_minimo" data-existencia value="{{ old('stock_minimo', $producto->stock_minimo ?? 5) }}" required class="campo">
        <p class="text-[11px] text-stone-500 dark:text-stone-400 mt-1">Se avisará en el panel cuando el stock caiga a este nivel o menos.</p>
    </div>
    <div>
        <label class="etiqueta">Stock máximo (opcional)</label>
        <input type="number" min="0" step="0.001" name="stock_maximo" data-existencia value="{{ old('stock_maximo', $producto->stock_maximo ?? '') }}" class="campo">
        <p class="text-[11px] text-stone-500 dark:text-stone-400 mt-1">Referencia para planificar compras.</p>
    </div>
</div>

@if(isset($producto))
    <div>
        <label class="etiqueta">Motivo del ajuste de stock (si lo cambias)</label>
        <input type="text" name="motivo_ajuste" maxlength="255" value="{{ old('motivo_ajuste') }}" placeholder="Ej. Conteo físico del sábado" class="campo">
        <p class="text-[11px] text-stone-500 dark:text-stone-400 mt-1">Para entradas de mercancía usa <strong>Compras</strong> y para pérdidas usa <strong>Mermas</strong>. Si cambias el stock aquí, queda registrado como ajuste en el kardex.</p>
    </div>

    <label class="flex items-start gap-3 p-3 rounded-md border border-stone-200 dark:border-stone-700 cursor-pointer">
        <input type="checkbox" name="activo" value="1" @checked(old('activo', $producto->activo)) class="mt-0.5 accent-emerald-600">
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
