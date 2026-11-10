{{-- Fila de producto de una venta.
     $i = índice, $linea = ['producto_id' => ..., 'cantidad' => ...] o null (fila vacía),
     $stockExtra = [producto_id => cantidad] que la venta en edición devolvería al inventario. --}}
@php
    $productoSel = $linea['producto_id'] ?? null;
    $cantidad = $linea['cantidad'] ?? 1;
    // La regla de decimales depende de la unidad de medida del producto elegido (el script la
    // reajusta al cambiarlo). Sin producto elegido no se restringe todavía.
    $productoFila = $productos->firstWhere('id', (int) $productoSel);
    $entera = $productoFila && $productoFila->usaCantidadEntera();
@endphp
<div class="item-row grid grid-cols-1 md:grid-cols-12 gap-4 items-center bg-stone-50 dark:bg-stone-900/60 p-4 rounded-md border border-stone-200 dark:border-stone-700">
    <div class="md:col-span-6">
        <label class="block text-xs font-semibold text-stone-500 dark:text-stone-400 uppercase mb-1.5">Producto</label>
        <select name="productos[{{ $i }}][id]" required class="campo producto-select">
            <option value="" data-precio="0">Seleccionar producto...</option>
            @foreach($productos as $prod)
                <option value="{{ $prod->id }}" data-precio="{{ $prod->precio }}" data-entero="{{ $prod->usaCantidadEntera() ? 1 : 0 }}" @selected((string) $productoSel === (string) $prod->id)>
                    {{ $prod->nombre }} — @usd($prod->precio) (@bs($prod->precio)) / {{ $prod->unidadLabel() }} (Stock: {{ \App\Support\Cantidad::formato($prod->stock + ($stockExtra[$prod->id] ?? 0)) }})
                </option>
            @endforeach
        </select>
    </div>
    <div class="md:col-span-3">
        <label class="block text-xs font-semibold text-stone-500 dark:text-stone-400 uppercase mb-1.5">Cantidad</label>
        <input type="number" name="productos[{{ $i }}][cantidad]" min="{{ $entera ? 1 : 0.001 }}" step="{{ $entera ? 1 : 0.001 }}" inputmode="{{ $entera ? 'numeric' : 'decimal' }}" data-entero="{{ $entera ? 1 : 0 }}" value="{{ is_numeric($cantidad) ? \App\Support\Cantidad::formato($cantidad) : $cantidad }}" required class="campo cantidad-input">    </div>
    <div class="md:col-span-2 font-bold text-stone-800 dark:text-stone-100 text-right">
        {{-- El subtotal lo calcula el script del formulario al cargar y al cambiar algo --}}
        Subtotal: $<span class="subtotal-span">0.00</span>
    </div>
    <div class="md:col-span-1 text-right">
        <button type="button" data-quitar-fila class="p-2 rounded-md bg-rose-50 hover:bg-rose-100 dark:bg-rose-900/20 dark:hover:bg-rose-900/40 text-rose-700 dark:text-rose-400 transition" title="Quitar producto">
            <x-icon name="x" class="w-4 h-4" />
        </button>
    </div>
</div>
