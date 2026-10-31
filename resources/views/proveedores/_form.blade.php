{{-- Formulario compartido entre crear y editar proveedor --}}
<div>
    <label class="block text-xs font-semibold text-stone-600 uppercase mb-1.5">Nombre del Proveedor</label>
    <input type="text" name="nombre" value="{{ old('nombre', $proveedor->nombre ?? '') }}" required placeholder="Ej. Distribuidora Central C.A." class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-emerald-500 focus:border-emerald-500">
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-xs font-semibold text-stone-600 uppercase mb-1.5">Persona de Contacto</label>
        <input type="text" name="contacto" value="{{ old('contacto', $proveedor->contacto ?? '') }}" placeholder="Ej. Juan Pérez" class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-emerald-500 focus:border-emerald-500">
    </div>
    <div>
        <label class="block text-xs font-semibold text-stone-600 uppercase mb-1.5">Teléfono</label>
        <input type="text" name="telefono" value="{{ old('telefono', $proveedor->telefono ?? '') }}" placeholder="0412-0000000" class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-emerald-500 focus:border-emerald-500">
    </div>
</div>

<div>
    <label class="block text-xs font-semibold text-stone-600 uppercase mb-1.5">WhatsApp</label>
    <input type="text" name="whatsapp" value="{{ old('whatsapp', $proveedor->whatsapp ?? '') }}" placeholder="0412-0000000" class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-emerald-500 focus:border-emerald-500">
    <p class="text-[11px] text-stone-400 mt-1">Incluye código de país si es posible (ej. 58412...). Se usa para el botón de chat directo.</p>
</div>

<div>
    <label class="block text-xs font-semibold text-stone-600 uppercase mb-1.5">Correo Electrónico</label>
    <input type="email" name="email" value="{{ old('email', $proveedor->email ?? '') }}" placeholder="correo@proveedor.com" class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-emerald-500 focus:border-emerald-500">
</div>

<div>
    <label class="block text-xs font-semibold text-stone-600 uppercase mb-1.5">Dirección</label>
    <textarea name="direccion" rows="2" class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-emerald-500 focus:border-emerald-500">{{ old('direccion', $proveedor->direccion ?? '') }}</textarea>
</div>

<div>
    <label class="block text-xs font-semibold text-stone-600 uppercase mb-1.5">Notas (Opcional)</label>
    <textarea name="notas" rows="2" placeholder="Condiciones de pago, productos que surte, etc." class="w-full rounded-lg border-stone-300 border p-2.5 text-sm bg-white text-stone-800 placeholder-stone-400 focus:ring-emerald-500 focus:border-emerald-500">{{ old('notas', $proveedor->notas ?? '') }}</textarea>
</div>

@if(isset($proveedor))
    <label class="flex items-center gap-2.5 pt-1">
        <input type="hidden" name="activo" value="0">
        <input type="checkbox" name="activo" value="1" {{ old('activo', $proveedor->activo) ? 'checked' : '' }} class="rounded border-stone-300 text-emerald-600 focus:ring-emerald-500">
        <span class="text-sm text-stone-600 font-semibold">Proveedor activo (disponible para nuevas compras)</span>
    </label>
@endif
