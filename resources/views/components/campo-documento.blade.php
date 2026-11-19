@props([
    'valor' => null,
    'label' => 'Cédula o RIF (Opcional)',
    'tipoInicial' => 'V',
    'ayuda' => 'V venezolano, E extranjero, J empresa, G gobierno, P pasaporte. Solo números.',
])

{{-- Cédula/RIF: tipo + número. El controlador lo valida con Contacto::reglasDocumento() y lo une con unirDocumento(). --}}
@php
    [$tipoGuardado, $numeroGuardado] = \App\Support\Contacto::separarDocumento($valor);
    $tipoGuardado = $valor ? $tipoGuardado : $tipoInicial;
@endphp
<div>
    <label class="etiqueta" for="documento_numero">{{ $label }}</label>
    <div class="flex gap-2">
        <select name="documento_tipo" aria-label="Tipo de documento" class="campo w-20 shrink-0 @error('documento_tipo') border-rose-500 @enderror">
            @foreach(\App\Support\Contacto::TIPOS_DOCUMENTO as $tipo)
                <option value="{{ $tipo }}" @selected(old('documento_tipo', $tipoGuardado) === $tipo)>{{ $tipo }}</option>
            @endforeach
        </select>
        <input id="documento_numero" type="text" name="documento_numero"
               value="{{ old('documento_numero', $numeroGuardado) }}"
               inputmode="numeric" maxlength="9" pattern="[0-9]{6,9}"
               title="Solo números, de 6 a 9 (ej. 12345678)"
               oninput="soloDigitos(this, 9)"
               placeholder="12345678"
               class="campo @error('documento_numero') border-rose-500 @enderror">
    </div>
    <p class="ayuda-campo">{{ $ayuda }}</p>
    @error('documento_tipo') <p class="error-campo">{{ $message }}</p> @enderror
    @error('documento_numero') <p class="error-campo">{{ $message }}</p> @enderror
</div>
