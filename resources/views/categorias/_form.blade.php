{{-- Formulario compartido entre crear y editar categoría --}}
<div class="flex items-center gap-4">
    <div class="shrink-0">
        @if(isset($categoria) && $categoria->imagenUrl())
            <img src="{{ $categoria->imagenUrl() }}" alt="{{ $categoria->nombre }}" class="w-14 h-14 rounded-lg object-cover border border-stone-300">
        @else
            <span class="w-14 h-14 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center border border-stone-300">
                <x-icon name="tag" class="w-5 h-5" />
            </span>
        @endif
    </div>
    <div class="flex-1">
        <label class="block text-xs font-semibold text-stone-600 uppercase mb-1.5">Imagen (Opcional)</label>
        <input type="file" name="imagen" accept="image/*" class="w-full text-sm text-stone-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:text-emerald-700 file:font-semibold hover:file:bg-emerald-100">
    </div>
</div>

<div>
    <label class="block text-xs font-semibold text-stone-600 uppercase mb-1.5">Nombre</label>
    <input type="text" name="nombre" value="{{ old('nombre', $categoria->nombre ?? '') }}" placeholder="Ej. Hortalizas, Granos..." required class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-emerald-500 focus:border-emerald-500">
</div>

<div>
    <label class="block text-xs font-semibold text-stone-600 uppercase mb-1.5">Descripción (Opcional)</label>
    <textarea name="descripcion" rows="3" class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-emerald-500 focus:border-emerald-500">{{ old('descripcion', $categoria->descripcion ?? '') }}</textarea>
</div>
