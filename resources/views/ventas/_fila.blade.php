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
<div class="item-row grid grid-cols-12 gap-x-3 gap-y-3 items-end py-4 first:pt-0 last:pb-0 border-b border-stone-200 dark:border-stone-700 last:border-b-0">
    <div class="col-span-12 sm:col-span-6">
        <label class="etiqueta" for="producto-{{ $i }}">Producto</label>
        <select id="producto-{{ $i }}" name="productos[{{ $i }}][id]" required class="campo producto-select">
            <option value="" data-precio="0">Selecciona el producto…</option>
            @foreach($productos as $prod)
                <option value="{{ $prod->id }}" data-precio="{{ $prod->precio }}" data-entero="{{ $prod->usaCantidadEntera() ? 1 : 0 }}" @selected((string) $productoSel === (string) $prod->id)>
                    {{ $prod->nombre }} — @usd($prod->precio) (@bs($prod->precio)) / {{ $prod->unidadLabel() }} (Stock: {{ \App\Support\Cantidad::formato($prod->stock + ($stockExtra[$prod->id] ?? 0)) }})
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-span-5 sm:col-span-2">
        <label class="etiqueta" for="cantidad-{{ $i }}">Cantidad</label>
        <input id="cantidad-{{ $i }}" type="number" name="productos[{{ $i }}][cantidad]" min="{{ $entera ? 1 : 0.001 }}" step="{{ $entera ? 1 : 0.001 }}" inputmode="{{ $entera ? 'numeric' : 'decimal' }}" data-entero="{{ $entera ? 1 : 0 }}" value="{{ is_numeric($cantidad) ? \App\Support\Cantidad::formato($cantidad) : $cantidad }}" max="999999" required class="campo cantidad-input">
    </div>
    <div class="col-span-5 sm:col-span-3">
        <span class="etiqueta">Subtotal</span>
        {{-- El subtotal lo calcula el script del formulario al cargar y al cambiar algo --}}
        <p class="caja-suave min-h-[2.625rem] px-3 flex items-center justify-end font-bold text-stone-800 dark:text-stone-100 whitespace-nowrap">$<span class="subtotal-span">0.00</span></p>
    </div>
    <div class="col-span-2 sm:col-span-1 flex justify-end">
        <button type="button" data-quitar-fila class="btn-icono-rojo w-[2.625rem] h-[2.625rem]" title="Quitar producto" aria-label="Quitar producto">
            <x-icon name="trash" class="w-4 h-4" />
        </button>
    </div>
</div>
