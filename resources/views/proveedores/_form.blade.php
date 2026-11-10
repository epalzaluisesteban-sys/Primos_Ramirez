{{-- Formulario compartido entre crear y editar proveedor --}}
<div>
    <label class="etiqueta" for="nombre">Nombre del proveedor</label>
    <input id="nombre" type="text" name="nombre" value="{{ old('nombre', $proveedor->nombre ?? '') }}" required placeholder="Ej. Distribuidora Central C.A." class="campo @error('nombre') border-rose-500 @enderror">
    @error('nombre') <p class="error-campo">{{ $message }}</p> @enderror
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="etiqueta" for="contacto">Persona de contacto (Opcional)</label>
        <input id="contacto" type="text" name="contacto" value="{{ old('contacto', $proveedor->contacto ?? '') }}" placeholder="Ej. Juan Pérez" class="campo @error('contacto') border-rose-500 @enderror">
        @error('contacto') <p class="error-campo">{{ $message }}</p> @enderror
    </div>
    <x-campo-telefono name="telefono" label="Teléfono (Opcional)" :valor="$proveedor->telefono ?? null" />
</div>

<x-campo-telefono name="whatsapp" label="WhatsApp (Opcional)" :valor="$proveedor->whatsapp ?? null"
                  ayuda="Solo números, 11 dígitos (ej. 04121234567). Activa el botón para escribirle directo." />

<div>
    <label class="etiqueta" for="email">Correo electrónico (Opcional)</label>
    <input id="email" type="email" name="email" value="{{ old('email', $proveedor->email ?? '') }}" placeholder="correo@proveedor.com" class="campo @error('email') border-rose-500 @enderror">
    @error('email') <p class="error-campo">{{ $message }}</p> @enderror
</div>

<div>
    <label class="etiqueta" for="direccion">Dirección (Opcional)</label>
    <textarea id="direccion" name="direccion" rows="2" class="campo @error('direccion') border-rose-500 @enderror">{{ old('direccion', $proveedor->direccion ?? '') }}</textarea>
    @error('direccion') <p class="error-campo">{{ $message }}</p> @enderror
</div>

<div>
    <label class="etiqueta" for="notas">Notas (Opcional)</label>
    <textarea id="notas" name="notas" rows="2" placeholder="Condiciones de pago, productos que surte, etc." class="campo @error('notas') border-rose-500 @enderror">{{ old('notas', $proveedor->notas ?? '') }}</textarea>
    @error('notas') <p class="error-campo">{{ $message }}</p> @enderror
</div>

@if(isset($proveedor))
    <label class="caja-suave flex items-start gap-3 p-3 cursor-pointer">
        <input type="hidden" name="activo" value="0">
        <input type="checkbox" name="activo" value="1" @checked(old('activo', $proveedor->activo)) class="casilla mt-0.5">
        <span>
            <span class="block text-sm font-semibold text-stone-700 dark:text-stone-200">Proveedor activo</span>
            <span class="block text-[11px] text-stone-500 dark:text-stone-400">Desmárcalo para que no aparezca en compras nuevas. Sus compras anteriores se conservan.</span>
        </span>
    </label>
@endif
