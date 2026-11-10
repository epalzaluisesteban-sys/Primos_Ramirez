{{-- Formulario compartido entre crear y editar categoría --}}
<div class="flex items-center gap-4">
    <div class="shrink-0">
        @if(isset($categoria) && $categoria->imagenUrl())
            <img src="{{ $categoria->imagenUrl() }}" alt="{{ $categoria->nombre }}" class="w-14 h-14 rounded-md object-cover border border-stone-300 dark:border-stone-700">
        @else
            <span class="w-14 h-14 rounded-md bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 flex items-center justify-center">
                <x-icon name="tag" class="w-5 h-5" />
            </span>
        @endif
    </div>
    <div class="flex-1 min-w-0">
        <label class="etiqueta" for="imagen">Imagen (Opcional)</label>
        <input id="imagen" type="file" name="imagen" accept="image/*" class="campo-archivo">
        @error('imagen') <p class="error-campo">{{ $message }}</p> @enderror
    </div>
</div>

<div>
    <label class="etiqueta" for="nombre">Nombre</label>
    <input id="nombre" type="text" name="nombre" value="{{ old('nombre', $categoria->nombre ?? '') }}" placeholder="Ej. Hortalizas, Granos…" required class="campo @error('nombre') border-rose-500 @enderror">
    @error('nombre') <p class="error-campo">{{ $message }}</p> @enderror
</div>

<div>
    <label class="etiqueta" for="descripcion">Descripción (Opcional)</label>
    <textarea id="descripcion" name="descripcion" rows="3" class="campo @error('descripcion') border-rose-500 @enderror">{{ old('descripcion', $categoria->descripcion ?? '') }}</textarea>
    @error('descripcion') <p class="error-campo">{{ $message }}</p> @enderror
</div>
