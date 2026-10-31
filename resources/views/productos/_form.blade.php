{{-- Formulario compartido entre crear y editar producto --}}
<div class="flex items-center gap-4">
    <div class="shrink-0">
        @if(isset($producto) && $producto->fotoUrl())
            <img src="{{ $producto->fotoUrl() }}" alt="{{ $producto->nombre }}" class="w-16 h-16 rounded-lg object-cover border border-stone-300 dark:border-stone-700">
        @else
            <span class="w-16 h-16 rounded-lg bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 flex items-center justify-center border border-stone-300 dark:border-stone-700">
                <x-icon name="box" class="w-6 h-6" />
            </span>
        @endif
    </div>
    <div class="flex-1">
        <label class="block text-xs font-semibold text-stone-600 dark:text-stone-300 uppercase mb-1.5">Fotografía (Opcional)</label>
        <input type="file" name="foto" accept="image/*" class="w-full text-sm text-stone-600 dark:text-stone-300 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:text-emerald-700 file:font-semibold hover:file:bg-emerald-100">
        <p class="text-[11px] text-stone-400 dark:text-stone-500 mt-1">JPG o PNG, máximo 2MB.</p>
    </div>
</div>

<div>
    <label class="block text-xs font-semibold text-stone-600 dark:text-stone-300 uppercase mb-1.5">Nombre del Producto</label>
    <input type="text" name="nombre" value="{{ old('nombre', $producto->nombre ?? '') }}" required placeholder="Ej. Cambur (Kg)" class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-stone-900 dark:border-stone-600 dark:text-stone-100 dark:placeholder-stone-500">
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-xs font-semibold text-stone-600 dark:text-stone-300 uppercase mb-1.5">Marca (Opcional)</label>
        <input type="text" name="marca" value="{{ old('marca', $producto->marca ?? '') }}" placeholder="Ej. Polar, Diana..." class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-stone-900 dark:border-stone-600 dark:text-stone-100 dark:placeholder-stone-500">
    </div>
    <div>
        <label class="block text-xs font-semibold text-stone-600 dark:text-stone-300 uppercase mb-1.5">Categoría</label>
        <select name="categoria_id" required class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-stone-900 dark:border-stone-600 dark:text-stone-100 dark:placeholder-stone-500">
            <option value="">-- Seleccionar Categoría --</option>
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
        <label class="block text-xs font-semibold text-stone-600 dark:text-stone-300 uppercase mb-1.5">Ubicación (Opcional)</label>
        <input type="text" name="ubicacion" value="{{ old('ubicacion', $producto->ubicacion ?? '') }}" placeholder="Ej. Pasillo 3, Anaquel B" class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-stone-900 dark:border-stone-600 dark:text-stone-100 dark:placeholder-stone-500">
    </div>
    <div>
        <label class="block text-xs font-semibold text-stone-600 dark:text-stone-300 uppercase mb-1.5">Unidad de Medida</label>
        <select name="unidad_medida" required class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-stone-900 dark:border-stone-600 dark:text-stone-100 dark:placeholder-stone-500">
            @foreach(\App\Models\Producto::UNIDADES as $key => $label)
                <option value="{{ $key }}" {{ old('unidad_medida', $producto->unidad_medida ?? 'unidad') == $key ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-xs font-semibold text-stone-600 dark:text-stone-300 uppercase mb-1.5">Código de Barras (Opcional)</label>
        <input type="text" name="codigo_barras" value="{{ old('codigo_barras', $producto->codigo_barras ?? '') }}" placeholder="Escanea o escribe el código" class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-stone-900 dark:border-stone-600 dark:text-stone-100 dark:placeholder-stone-500">
    </div>
    <div>
        <label class="block text-xs font-semibold text-stone-600 dark:text-stone-300 uppercase mb-1.5">Código QR (Opcional)</label>
        <input type="text" name="codigo_qr" value="{{ old('codigo_qr', $producto->codigo_qr ?? '') }}" placeholder="Déjalo vacío para generarlo automático" class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-stone-900 dark:border-stone-600 dark:text-stone-100 dark:placeholder-stone-500">
    </div>
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-xs font-semibold text-stone-600 dark:text-stone-300 uppercase mb-1.5">Precio de Compra ($, Opcional)</label>
        <input type="number" step="0.01" min="0" id="input-precio-compra" name="precio_compra" value="{{ old('precio_compra', $producto->precio_compra ?? '') }}" placeholder="Costo de referencia" class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-stone-900 dark:border-stone-600 dark:text-stone-100 dark:placeholder-stone-500">
    </div>
    <div>
        <label class="block text-xs font-semibold text-stone-600 dark:text-stone-300 uppercase mb-1.5">Precio de Venta ($) <span id="hint-bs" class="normal-case font-medium text-stone-400 dark:text-stone-500" data-tasa="{{ \App\Support\Moneda::tasa() }}"></span></label>
        <input type="number" step="0.01" min="0" id="input-precio-venta" name="precio" value="{{ old('precio', $producto->precio ?? '') }}" required placeholder="0.00" class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-stone-900 dark:border-stone-600 dark:text-stone-100 dark:placeholder-stone-500">
    </div>
</div>
<p class="text-xs font-semibold -mt-2">
    Margen de ganancia: <span id="texto-margen" class="text-emerald-700 dark:text-emerald-400">—</span>
</p>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-xs font-semibold text-stone-600 dark:text-stone-300 uppercase mb-1.5">Stock {{ isset($producto) ? 'Actual' : 'Inicial' }}</label>
        <input type="number" min="0" step="0.001" name="stock" value="{{ old('stock', $producto->stock ?? 0) }}" required class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-stone-900 dark:border-stone-600 dark:text-stone-100 dark:placeholder-stone-500">
    </div>
    <div>
        <label class="block text-xs font-semibold text-stone-600 dark:text-stone-300 uppercase mb-1.5">Fecha de Vencimiento (Opcional)</label>
        <input type="date" name="fecha_vencimiento" value="{{ old('fecha_vencimiento', isset($producto->fecha_vencimiento) ? $producto->fecha_vencimiento?->format('Y-m-d') : '') }}" class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-stone-900 dark:border-stone-600 dark:text-stone-100 dark:placeholder-stone-500">
        <p class="text-[11px] text-stone-500 dark:text-stone-400 mt-1">Del lote actual en anaquel. Útil para perecederos.</p>
    </div>
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-xs font-semibold text-stone-600 dark:text-stone-300 uppercase mb-1.5">Stock Mínimo (Alerta)</label>
        <input type="number" min="0" step="0.001" name="stock_minimo" value="{{ old('stock_minimo', $producto->stock_minimo ?? 5) }}" required class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-stone-900 dark:border-stone-600 dark:text-stone-100 dark:placeholder-stone-500">
        <p class="text-[11px] text-stone-500 dark:text-stone-400 mt-1">Se avisará en el panel cuando el stock caiga a este nivel o menos.</p>
    </div>
    <div>
        <label class="block text-xs font-semibold text-stone-600 dark:text-stone-300 uppercase mb-1.5">Stock Máximo (Opcional)</label>
        <input type="number" min="0" step="0.001" name="stock_maximo" value="{{ old('stock_maximo', $producto->stock_maximo ?? '') }}" class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-stone-900 dark:border-stone-600 dark:text-stone-100 dark:placeholder-stone-500">
        <p class="text-[11px] text-stone-500 dark:text-stone-400 mt-1">Referencia para planificar compras.</p>
    </div>
</div>

@if(isset($producto))
    <p class="text-xs text-stone-500 dark:text-stone-400 -mt-2">Para registrar entradas de mercancía usa el módulo de <strong>Compras</strong>; edita el stock aquí solo para correcciones puntuales.</p>
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
