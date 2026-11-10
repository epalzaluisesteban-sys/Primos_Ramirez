@props(['name' => 'telefono', 'label' => 'Teléfono', 'valor' => null, 'ayuda' => null])

{{-- Campo de teléfono venezolano: solo números, 11 dígitos empezando por 0 (04121234567).
     Las letras y guiones se borran al escribir; el servidor vuelve a validarlo (App\Support\Contacto). --}}
<div {{ $attributes }}>
    <label class="etiqueta" for="{{ $name }}">{{ $label }}</label>
    <input id="{{ $name }}" type="tel" name="{{ $name }}"
           value="{{ old($name, \App\Support\Contacto::limpiarTelefono($valor)) }}"
           inputmode="numeric" autocomplete="tel" maxlength="11" pattern="0[0-9]{10}"
           title="11 números, empezando por 0 (ej. 04121234567)"
           oninput="soloDigitos(this, 11, true)"
           placeholder="04121234567"
           class="campo @error($name) border-rose-500 @enderror">
    <p class="ayuda-campo">{{ $ayuda ?? 'Solo números, 11 dígitos (ej. 04121234567).' }}</p>
    @error($name) <p class="error-campo">{{ $message }}</p> @enderror
</div>
