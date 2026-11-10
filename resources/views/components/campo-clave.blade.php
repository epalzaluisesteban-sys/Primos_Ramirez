@props([
    'name' => 'password',
    'label' => 'Contraseña',
    'id' => null,
    'placeholder' => '••••••••',
    'autocomplete' => 'current-password',
    'ayuda' => null,
])

{{-- Campo de contraseña con botón para verla u ocultarla (el "ojito"). Usar en TODO campo de contraseña.
     Los atributos extra (required, minlength, autofocus…) pasan directo al <input>.
     Uso: <x-campo-clave name="password" label="Nueva contraseña" autocomplete="new-password" minlength="8" /> --}}
@php $id = $id ?? $name; @endphp

<div>
    <label class="etiqueta" for="{{ $id }}">{{ $label }}</label>
    <div class="relative">
        <input id="{{ $id }}" type="password" name="{{ $name }}" placeholder="{{ $placeholder }}" autocomplete="{{ $autocomplete }}"
               {{ $attributes->merge(['class' => 'campo pr-11' . ($errors->has($name) ? ' border-rose-500' : '')]) }}>
        <button type="button" onclick="alternarClave(this)" data-campo="{{ $id }}"
                class="absolute inset-y-0 right-0 flex items-center px-3 rounded-r-md text-stone-500 hover:text-stone-800 dark:text-stone-400 dark:hover:text-stone-100"
                aria-label="Mostrar contraseña" aria-pressed="false" title="Mostrar contraseña">
            <x-icon name="eye" class="w-5 h-5" />
            <x-icon name="eye-off" class="w-5 h-5 hidden" />
        </button>
    </div>
    @if($ayuda)
        <p class="ayuda-campo">{{ $ayuda }}</p>
    @endif
    @error($name) <p class="error-campo">{{ $message }}</p> @enderror
</div>
