---
name: formularios
description: Convenciones para crear o modificar formularios Blade en Los Primos Ramírez (validación, conservar datos, mostrar errores, montos en $ y Bs., confirmaciones). Úsala siempre que toques un <form>.
---

# Formularios

Objetivo: que una persona sin experiencia técnica entienda qué escribir y qué salió mal.

## Estructura
- `<form method="POST" action="{{ route(...) }}">` + `@csrf` (+ `@method('PUT')` al editar).
  Con archivos: `enctype="multipart/form-data"`.
- Crear y editar comparten `_form.blade.php`; distingue con `isset($modelo)`.
- Agrupa campos relacionados en bloques con un título corto; lo opcional dice "(Opcional)" en la etiqueta.
- Debajo del campo, una ayuda de una línea con `class="ayuda-campo"` cuando no sea obvio.
- Campos cortos en pareja: `grid grid-cols-1 sm:grid-cols-2 gap-4` (en móvil quedan uno debajo del otro).
- Vista contenedora (modelo: `proveedores/create.blade.php`): `max-w-xl`, `<div class="tarjeta"><form class="space-y-4">`,
  botonera `flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-1` con Cancelar (`btn-secundario`) y
  Guardar (`btn-primario` + ícono). El texto del botón dice la acción: "Guardar proveedor", "Registrar abono".

## Accesibilidad y uso en el teléfono
- Cada campo con `id` y su `<label class="etiqueta" for="ese-id">`. `required` en lo obligatorio.
- `placeholder` solo como ejemplo ("Ej. Juan Pérez"), nunca en lugar de la etiqueta.
- Teclado correcto: dinero `inputmode="decimal"`, correo `type="email"`. `autocomplete="off"` en buscadores.
- **Contraseña:** siempre `<x-campo-clave name="password" label="Contraseña" />` (trae el ojito para verla, la etiqueta
  y el error). Para claves nuevas pasa `autocomplete="new-password"`; `required`, `minlength`… pasan directo al input.
  Nunca un `<input type="password">` suelto.
- **Teléfono:** siempre `<x-campo-telefono name="telefono" label="Teléfono (Opcional)" :valor="$modelo->telefono ?? null" />`
  (solo números, 11 dígitos empezando por 0). En el controlador: `Contacto::limpiarTelefono()` con `$request->merge()`
  antes de validar y la regla `Contacto::REGLA_TELEFONO`. Para mostrarlo: `Contacto::telefono($valor)` → 0412-1234567.
- **Cédula/RIF:** select de tipo (`Contacto::TIPOS_DOCUMENTO`) + número con `oninput="soloDigitos(this, 9)"`;
  se guarda con `Contacto::unirDocumento()` (modelo: `clientes/_form.blade.php` y `ClienteController`).
- `autofocus` en el primer campo de formularios que se llenan muchas veces al día (venta, abono).

## Valores y errores
- Siempre `value="{{ old('campo', $modelo->campo ?? '') }}"`, para que no se pierda lo escrito si hay error.
  En selects: `@selected(old('campo', $modelo->campo ?? null) == $opcion)`; casillas: `@checked(old(...))`.
- Selects de registros (productos, clientes, proveedores): solo los `activo`; primera opción vacía
  "Selecciona…" cuando sea obligatorio elegir.
- Casilla booleana: manda `0` cuando está desmarcada con un hidden antes del checkbox, y envuélvela en una
  etiqueta clicable con explicación (modelo: "Proveedor activo" en `proveedores/_form.blade.php`):
  ```blade
  <label class="caja-suave flex items-start gap-3 p-3 cursor-pointer">
      <input type="hidden" name="activo" value="0">
      <input type="checkbox" name="activo" value="1" @checked(old('activo', $modelo->activo ?? true)) class="casilla mt-0.5">
      <span><span class="block text-sm font-semibold">Activo</span><span class="ayuda-campo block">Qué pasa si lo desmarcas.</span></span>
  </label>
  ```
- Muestra el error debajo de cada campo:
  ```blade
  @error('campo') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
  ```
  (o `class="error-campo"`) y resalta el input con `@error('campo') border-rose-500 @enderror`.
- El controlador devuelve `back()->withErrors([...])->withInput()`; los errores generales van en la clave `error`
  y el layout los muestra como aviso (toast).
- Mensajes de validación en español en `lang/es/validation.php`; nombres de campo legibles en su arreglo `attributes`.

## Clases de formulario (claro/oscuro ya incluidos, definidas en `resources/css/app.css`)
```blade
<label class="etiqueta">Nombre</label>
<input type="text" name="nombre" class="campo @error('nombre') border-rose-500 @enderror">   {{-- también select y textarea --}}
<p class="ayuda-campo">Una línea de ayuda.</p>
@error('nombre') <p class="error-campo">{{ $message }}</p> @enderror
<input type="file" class="campo-archivo">   <input type="checkbox" class="casilla">
<button type="submit" class="btn-primario"><x-icon name="check" class="w-4 h-4" /> Guardar</button>
<a href="..." class="btn-secundario">Cancelar</a>
```
No copies las clases largas de Tailwind en los inputs: si falta algo, agrégalo a la clase compartida.

## Números y dinero
- Dinero en USD: `type="number" step="0.01" min="0"`; cantidades: `step="0.001" min="0.001"`.
- Si el monto es en $, muestra el equivalente en Bs. con la tasa vigente (`\App\Support\Moneda::tasa()`), como en `productos/_form`.
- Moneda de cobro: radios `USD` / `VES`; deshabilita `VES` si no hay tasa.

## Acciones peligrosas
- Borrar, anular o cerrar: agrega `data-confirm="¿Pregunta clara con la consecuencia?"` al `<form>`;
  el layout muestra el modal. No uses `confirm()` nativo.
- El botón de envío se deshabilita solo (loader del layout); no agregues JS propio para eso.

## Seguridad
- Nunca `{!! !!}` con datos del usuario. No pases `$request->all()` a `create()`: usa `only([...])` o los datos validados.
