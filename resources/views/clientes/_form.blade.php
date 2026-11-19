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
        <label class="etiqueta" for="foto">Foto (Opcional)</label>
        <input id="foto" type="file" name="foto" accept="image/*" class="campo-archivo">
        <p class="ayuda-campo">JPG o PNG, máximo 2MB.</p>
        @error('foto') <p class="error-campo">{{ $message }}</p> @enderror
    </div>
</div>

<div>
    <label class="etiqueta" for="nombre">Nombre del cliente</label>
    <input id="nombre" type="text" name="nombre" value="{{ old('nombre', $cliente->nombre ?? '') }}" required placeholder="Ej. María González" class="campo @error('nombre') border-rose-500 @enderror">
    @error('nombre') <p class="error-campo">{{ $message }}</p> @enderror
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <x-campo-telefono name="telefono" label="Teléfono (Opcional)" :valor="$cliente->telefono ?? null" />

    <x-campo-documento :valor="$cliente->rif ?? null"
                       ayuda="V venezolano, E extranjero, J empresa, G gobierno, P pasaporte. Solo números. No se puede repetir entre clientes." />
</div>

<div>
    <label class="etiqueta" for="limite_credito">Límite de crédito ($, Opcional)</label>
    <input id="limite_credito" type="number" step="0.01" min="0" inputmode="decimal" name="limite_credito" value="{{ old('limite_credito', $cliente->limite_credito ?? '') }}" placeholder="Sin límite" class="campo @error('limite_credito') border-rose-500 @enderror">
    @error('limite_credito') <p class="error-campo">{{ $message }}</p> @enderror
</div>

<div>
    <label class="etiqueta" for="direccion">Dirección (Opcional)</label>
    <textarea id="direccion" name="direccion" rows="2" class="campo @error('direccion') border-rose-500 @enderror">{{ old('direccion', $cliente->direccion ?? '') }}</textarea>
    @error('direccion') <p class="error-campo">{{ $message }}</p> @enderror
</div>

<div>
    <label class="etiqueta" for="notas">Notas (Opcional)</label>
    <textarea id="notas" name="notas" rows="2" placeholder="Referencias, preferencias, etc." class="campo @error('notas') border-rose-500 @enderror">{{ old('notas', $cliente->notas ?? '') }}</textarea>
    @error('notas') <p class="error-campo">{{ $message }}</p> @enderror
</div>

@if(isset($cliente))
    <label class="caja-suave flex items-start gap-3 p-3 cursor-pointer">
        <input type="hidden" name="activo" value="0">
        <input type="checkbox" name="activo" value="1" @checked(old('activo', $cliente->activo)) class="casilla mt-0.5">
        <span>
            <span class="block text-sm font-semibold text-stone-700 dark:text-stone-200">Cliente activo</span>
            <span class="block text-[11px] text-stone-500 dark:text-stone-400">Desmárcalo para que no aparezca en ventas nuevas a crédito. Sus ventas y abonos se conservan.</span>
        </span>
    </label>
@endif
