{{-- Formulario compartido entre crear y editar proveedor --}}
<div>
    <label class="etiqueta" for="nombre">Nombre del proveedor</label>
    <input id="nombre" type="text" name="nombre" value="{{ old('nombre', $proveedor->nombre ?? '') }}" required minlength="2" maxlength="255" placeholder="Ej. Distribuidora Central C.A." class="campo @error('nombre') border-rose-500 @enderror">
    @error('nombre') <p class="error-campo">{{ $message }}</p> @enderror
</div>

<x-campo-documento :valor="$proveedor->rif ?? null" label="RIF o cédula (Opcional)" tipo-inicial="J"
                   ayuda="J empresa, V persona natural, G gobierno. Solo números; el RIF de empresa lleva 9. No se puede repetir entre proveedores." />

@php $categoriasElegidas = collect(old('categorias', isset($proveedor) ? $proveedor->categorias->pluck('id')->all() : []))->map(fn ($id) => (int) $id); @endphp
<fieldset>
    <legend class="etiqueta">Qué distribuye</legend>
    @if($categorias->isEmpty())
        <p class="ayuda-campo">Todavía no hay categorías. <a href="{{ route('categorias.index') }}" class="font-semibold text-emerald-700 dark:text-emerald-400 hover:underline">Crea las categorías</a> (frutas, verduras…) para marcarlas aquí.</p>
    @else
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
            @foreach($categorias as $categoria)
                <label class="caja-suave flex items-center gap-2 p-2.5 cursor-pointer text-sm text-stone-700 dark:text-stone-200">
                    <input type="checkbox" name="categorias[]" value="{{ $categoria->id }}" @checked($categoriasElegidas->contains($categoria->id)) class="casilla">
                    {{ $categoria->nombre }}
                </label>
            @endforeach
        </div>
        <p class="ayuda-campo">Marca las categorías de productos que te trae. Se muestran al elegirlo en una compra.</p>
    @endif
    @error('categorias') <p class="error-campo">{{ $message }}</p> @enderror
    @error('categorias.*') <p class="error-campo">{{ $message }}</p> @enderror
</fieldset>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-start">
    <label class="caja-suave flex items-start gap-3 p-3 cursor-pointer">
        <input type="hidden" name="da_credito" value="0">
        <input type="checkbox" name="da_credito" value="1" @checked(old('da_credito', $proveedor->da_credito ?? false)) class="casilla mt-0.5">
        <span>
            <span class="block text-sm font-semibold text-stone-700 dark:text-stone-200">Da crédito</span>
            <span class="block text-[11px] text-stone-500 dark:text-stone-400">Permite registrarle compras a crédito y llevar lo que se le debe.</span>
        </span>
    </label>
    <div>
        <label class="etiqueta" for="dias_credito">Días para pagar (Opcional)</label>
        <input id="dias_credito" type="number" name="dias_credito" min="1" max="365" step="1" inputmode="numeric"
               value="{{ old('dias_credito', $proveedor->dias_credito ?? '') }}" placeholder="Ej. 15" class="campo @error('dias_credito') border-rose-500 @enderror">
        <p class="ayuda-campo">Con esto cada compra a crédito lleva su fecha de vencimiento.</p>
        @error('dias_credito') <p class="error-campo">{{ $message }}</p> @enderror
    </div>
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
    <textarea id="notas" name="notas" rows="2" placeholder="Ej. Entrega martes y viernes" class="campo @error('notas') border-rose-500 @enderror">{{ old('notas', $proveedor->notas ?? '') }}</textarea>
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
