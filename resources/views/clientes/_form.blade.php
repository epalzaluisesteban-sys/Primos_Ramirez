{{-- Formulario compartido entre crear y editar cliente --}}
@php
    [$documentoTipo, $documentoNumero] = \App\Support\Contacto::separarDocumento($cliente->rif ?? null);
@endphp

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

    <div>
        <label class="etiqueta" for="documento_numero">Cédula o RIF (Opcional)</label>
        <div class="flex gap-2">
            <select name="documento_tipo" aria-label="Tipo de documento" class="campo w-20 shrink-0 @error('documento_tipo') border-rose-500 @enderror">
                @foreach(\App\Support\Contacto::TIPOS_DOCUMENTO as $tipo)
                    <option value="{{ $tipo }}" @selected(old('documento_tipo', $documentoTipo) === $tipo)>{{ $tipo }}</option>
                @endforeach
            </select>
            <input id="documento_numero" type="text" name="documento_numero"
                   value="{{ old('documento_numero', $documentoNumero) }}"
                   inputmode="numeric" maxlength="9" pattern="[0-9]{6,9}"
                   title="Solo números, de 6 a 9 (ej. 12345678)"
                   oninput="soloDigitos(this, 9)"
                   placeholder="12345678"
                   class="campo @error('documento_numero') border-rose-500 @enderror">
        </div>
        <p class="ayuda-campo">V venezolano, E extranjero, J empresa, G gobierno, P pasaporte. Solo números.</p>
        @error('documento_tipo') <p class="error-campo">{{ $message }}</p> @enderror
        @error('documento_numero') <p class="error-campo">{{ $message }}</p> @enderror
    </div>
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
