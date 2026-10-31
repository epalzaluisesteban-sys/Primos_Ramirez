{{-- Fila de producto de una venta. $i = índice, $det = DetalleVenta existente o null. --}}
<div class="item-row grid grid-cols-1 md:grid-cols-12 gap-4 items-center bg-stone-50 dark:bg-stone-900/60 p-4 rounded-xl border border-stone-200 dark:border-stone-700">
    <div class="md:col-span-6">
        <label class="block text-xs font-semibold text-stone-500 dark:text-stone-400 uppercase mb-1.5">Producto</label>
        <select name="productos[{{ $i }}][id]" required class="producto-select w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 dark:bg-stone-900 dark:border-stone-600 dark:text-stone-100 focus:ring-emerald-500 focus:border-emerald-500">
            <option value="" data-precio="0">Seleccionar producto...</option>
            @foreach($productos as $prod)
                <option value="{{ $prod->id }}" data-precio="{{ $prod->precio }}" @selected($det && $prod->id === $det->producto_id)>
                    {{ $prod->nombre }} — @usd($prod->precio) (@bs($prod->precio)) / {{ $prod->unidadLabel() }} (Stock: {{ $prod->stock + ($det && $prod->id === $det->producto_id ? $det->cantidad : 0) }})
                </option>
            @endforeach
        </select>
    </div>
    <div class="md:col-span-3">
        <label class="block text-xs font-semibold text-stone-500 dark:text-stone-400 uppercase mb-1.5">Cantidad</label>
        <input type="number" name="productos[{{ $i }}][cantidad]" min="0.001" step="0.001" value="{{ $det->cantidad ?? 1 }}" required class="cantidad-input w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 dark:bg-stone-900 dark:border-stone-600 dark:text-stone-100 focus:ring-emerald-500 focus:border-emerald-500">
    </div>
    <div class="md:col-span-2 font-bold text-stone-800 dark:text-stone-100 text-right">
        Subtotal: $<span class="subtotal-span">{{ number_format($det->subtotal ?? 0, 2) }}</span>
    </div>
    <div class="md:col-span-1 text-right">
        <button type="button" data-quitar-fila class="p-2 rounded-lg bg-rose-50 hover:bg-rose-100 dark:bg-rose-900/20 dark:hover:bg-rose-900/40 text-rose-700 dark:text-rose-400 transition" title="Quitar producto">
            <x-icon name="x" class="w-4 h-4" />
        </button>
    </div>
</div>
