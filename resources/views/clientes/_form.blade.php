{{-- Formulario compartido entre crear y editar cliente --}}
<div class="flex items-center gap-4">
    <div class="shrink-0">
        @if(isset($cliente) && $cliente->fotoUrl())
            <img src="{{ $cliente->fotoUrl() }}" alt="{{ $cliente->nombre }}" class="w-16 h-16 rounded-full object-cover border border-stone-300 dark:border-stone-700">
        @else
            <span class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 font-bold text-lg flex items-center justify-center border border-stone-300 dark:border-stone-700">
                {{ isset($cliente) ? strtoupper(substr($cliente->nombre, 0, 1)) : '?' }}
            </span>
        @endif
    </div>
    <div class="flex-1">
        <label class="block text-xs font-semibold text-stone-600 dark:text-stone-300 uppercase mb-1.5">Foto (Opcional)</label>
        <input type="file" name="foto" accept="image/*" class="w-full text-sm text-stone-600 dark:text-stone-300 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:text-emerald-700 file:font-semibold hover:file:bg-emerald-100 dark:file:bg-emerald-900/40 dark:file:text-emerald-300 dark:hover:file:bg-emerald-900/60">
        <p class="text-[11px] text-stone-400 dark:text-stone-500 mt-1">JPG o PNG, máximo 2MB.</p>
    </div>
</div>

<div>
    <label class="block text-xs font-semibold text-stone-600 dark:text-stone-300 uppercase mb-1.5">Nombre del Cliente</label>
    <input type="text" name="nombre" value="{{ old('nombre', $cliente->nombre ?? '') }}" required placeholder="Ej. María González" class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-stone-900 dark:border-stone-600 dark:text-stone-100 dark:placeholder-stone-500">
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-xs font-semibold text-stone-600 dark:text-stone-300 uppercase mb-1.5">Teléfono</label>
        <input type="text" name="telefono" value="{{ old('telefono', $cliente->telefono ?? '') }}" placeholder="0412-0000000" class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-stone-900 dark:border-stone-600 dark:text-stone-100 dark:placeholder-stone-500">
    </div>
    <div>
        <label class="block text-xs font-semibold text-stone-600 dark:text-stone-300 uppercase mb-1.5">RIF / C.I. (Opcional)</label>
        <input type="text" name="rif" value="{{ old('rif', $cliente->rif ?? '') }}" placeholder="V-12345678" class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-stone-900 dark:border-stone-600 dark:text-stone-100 dark:placeholder-stone-500">
    </div>
</div>

<div>
    <label class="block text-xs font-semibold text-stone-600 dark:text-stone-300 uppercase mb-1.5">Límite de Crédito ($, Opcional)</label>
    <input type="number" step="0.01" min="0" name="limite_credito" value="{{ old('limite_credito', $cliente->limite_credito ?? '') }}" placeholder="Sin límite" class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-stone-900 dark:border-stone-600 dark:text-stone-100 dark:placeholder-stone-500">
</div>

<div>
    <label class="block text-xs font-semibold text-stone-600 dark:text-stone-300 uppercase mb-1.5">Dirección</label>
    <textarea name="direccion" rows="2" class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-stone-900 dark:border-stone-600 dark:text-stone-100 dark:placeholder-stone-500">{{ old('direccion', $cliente->direccion ?? '') }}</textarea>
</div>

<div>
    <label class="block text-xs font-semibold text-stone-600 dark:text-stone-300 uppercase mb-1.5">Notas (Opcional)</label>
    <textarea name="notas" rows="2" placeholder="Referencias, preferencias, etc." class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-stone-900 dark:border-stone-600 dark:text-stone-100 dark:placeholder-stone-500">{{ old('notas', $cliente->notas ?? '') }}</textarea>
</div>

@if(isset($cliente))
    <label class="flex items-center gap-2.5 pt-1">
        <input type="hidden" name="activo" value="0">
        <input type="checkbox" name="activo" value="1" {{ old('activo', $cliente->activo) ? 'checked' : '' }} class="rounded border-stone-300 dark:border-stone-600 text-emerald-600 focus:ring-emerald-500">
        <span class="text-sm text-stone-700 dark:text-stone-300 font-semibold">Cliente activo (disponible para nuevas ventas a crédito)</span>
    </label>
@endif
