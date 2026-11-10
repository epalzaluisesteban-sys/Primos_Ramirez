---
name: interfaz
description: Patrones de interfaz de Los Primos Ramírez - cómo armar pantallas de listado, crear/editar y detalle con las piezas de app.css (tarjeta, tabla, insignias, botones, avisos), íconos, montos en $ y Bs., modo oscuro, móvil e impresión. Úsala al crear o cambiar cualquier vista Blade o al revisar cómo se ve una pantalla.
---

# Interfaz

Público: personal de una distribuidora, muchas veces desde el teléfono y sin experiencia técnica.
Cada pantalla debe decir **para qué sirve** y **qué hacer después**, en español sencillo.

## Reglas de oro
1. Usa las piezas de `resources/css/app.css` (ya traen modo oscuro). No copies cadenas largas de Tailwind;
   si una pieza no alcanza, ajústala con utilidades (`tarjeta p-0`, `btn-primario w-full`) o agrégala a `app.css`
   y corre `npm run build`.
2. Toda clase de color lleva su par `dark:` (o usa una pieza que ya lo tenga). Base `stone`; verde = acción/éxito,
   naranja/ámbar = atención o fiado, rojo = peligro/anulado, azul = información.
3. Íconos solo con `<x-icon name="..." class="w-4 h-4" />`. Sin emojis. Si falta uno, agrega su SVG
   (trazo `stroke-width="1.75"`, `viewBox="0 0 24 24"`) en `components/icon.blade.php`.
   Disponibles: cart, banknote, chart-bar, box, inbox-down, tag, users, user-circle, logout, alert-triangle,
   trending-up, trending-down, clock, check, check-circle, chevron-down, menu, pie, search, x, calendar, download,
   plus, key, lock, edit, trash, arrow-left, crown, truck, bell, package-x, leaf, apple, carrot, ban, printer,
   file-spreadsheet, whatsapp, sun, moon, spinner.
4. Los avisos de éxito/error los muestra el layout como toast. No repitas `session('success')` ni `$errors->any()`.
   Desde JS: `mostrarToast('Texto', 'success'|'error')`.
5. Sin scroll horizontal de la página en ningún ancho, de 320 px a 1920 px (las tablas sí pueden desplazarse
   dentro de `overflow-x-auto`). Ojo con 1280–1536 px: ahí se muestra el menú de escritorio completo; si agregas una
   opción al menú, comprueba que la barra siga cabiendo en 1280 px. Nada de anchos fijos sin `max-w-full`.

## Esqueleto de una vista
```blade
@extends('layouts.app')
@section('title', 'Proveedores')   {{-- el mismo texto que el título del encabezado --}}
@section('content')
<div class="pagina">   {{-- formularios de crear/editar y perfil: pagina-angosta --}}
    <x-encabezado titulo="Proveedores" subtitulo="Quién te surte la mercancía. Se eligen al registrar una compra.">
        <a href="{{ route('proveedores.create') }}" class="btn-primario"><x-icon name="plus" class="w-4 h-4" /> Nuevo proveedor</a>
    </x-encabezado>
    ...
</div>
@endsection
```
- **Solo dos anchos**: `pagina` (listados, detalles, reportes, ventas) y `pagina-angosta` (crear/editar, perfil).
  Nunca `max-w-*` a mano en el contenedor de la página.
- **Encabezado siempre con `<x-encabezado>`**: `titulo`, `subtitulo`, `:volver="route(...)"` en pantallas hijas,
  acciones en el slot, y slots `inicio` (foto/inicial) y `detalle` (datos bajo el título) para fichas. Sin íconos en el título.
- **Volver**: solo con `:volver` del encabezado. Es un único botón (`btn-secundario btn-sm` + `arrow-left` + "Volver")
  que el componente pone siempre arriba a la derecha, también en el teléfono. No crees enlaces "Volver" a mano.
- Títulos en mayúscula inicial ("Nueva venta", no "Nueva Venta") y con el mismo nombre que la opción del menú.
- Bloques dentro de la página: `titulo-seccion` (ej. "Resumen"); títulos de tarjeta: `titulo-tarjeta`, sin ícono delante.
- Cifras clave: `<div class="tarjeta p-4"><p class="cifra-etiqueta">…</p><p class="cifra-valor">…</p></div>`,
  sin franjas de color (`border-t-4`) ni íconos en la esquina; el color va en el valor si significa algo.
- Redondeo: todo `rounded-md` (ya viene en las piezas). Nada de `rounded-xl/2xl/3xl` ni píldoras `rounded-full`
  salvo avatares y puntos. Estados con `insignia-*`.
- CSS/JS solo de esa vista: `@push('head')` / `@push('scripts')`.
- El subtítulo explica el propósito en una frase, no repite el título.

## Pantalla de listado (modelo: `proveedores/index.blade.php`)
- Una `tarjeta p-0 overflow-hidden` con cabecera (`titulo-tarjeta` + `insignia-gris` con el conteo + buscador)
  separada por `border-b border-stone-200 dark:border-stone-700`.
- Buscador en vivo: `<input id="buscador-x" oninput="filtrarTabla('buscador-x', 'tabla-x')" class="campo pl-10">`
  con `<x-icon name="search">` posicionado a la izquierda. `filtrarTabla()` ya está en el layout.
- Tabla: `<div class="overflow-x-auto"><table class="tabla" id="tabla-x">`. Números a la derecha (`text-right`),
  estados al centro con insignias, acciones a la derecha en `flex justify-end gap-2`.
- Vacío: `@forelse … @empty <td colspan="N" class="tabla-vacia">Todavía no hay X. <a …>Registra el primero</a>.</td>`.
- Registros inactivos o anulados: fila con `opacity-60` + insignia (`insignia-gris` Inactivo, `insignia-roja` Anulada).
- Acciones por fila con `btn-icono-*` y **siempre** `title="..."` (y `aria-label` igual si no hay texto):
  ver = gris, editar = ámbar, activar/WhatsApp = verde, eliminar = rojo, desactivar = gris con ícono `ban`.
- Si hay acciones que confunden (desactivar vs eliminar), una leyenda pequeña al pie (`text-[11px]`).
- Exportar: `<x-acciones-exportar :excel="route(...)" :pdf="route(...)" />` en la cabecera de la página.

## Crear / editar (modelo: `proveedores/create.blade.php`)
- Contenedor angosto `max-w-xl mx-auto`, botón "Volver" `btn-secundario btn-sm` con `arrow-left` junto al título.
- `<div class="tarjeta"><form class="space-y-4">` + `@include('modulo._form')`.
- Botonera: `flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-1` → Cancelar (`btn-secundario`) y
  Guardar (`btn-primario` con ícono). El verbo del botón dice lo que pasa: "Guardar proveedor", "Registrar venta".
- Detalles del formulario: skill `formularios`.

## Detalle (show) y resúmenes
- Cifras clave arriba en una cuadrícula `grid grid-cols-2 lg:grid-cols-4 gap-4` de `tarjeta p-4`
  (etiqueta pequeña `text-xs uppercase text-stone-500` + valor `font-display text-xl font-bold`).
- Totales dentro de una tarjeta: `caja-suave p-4`.
- Avisos en página (no toast) para estados que persisten: `aviso-ambar` (atención, ej. caja cerrada, sin tasa),
  `aviso-rojo` (bloqueo), `aviso-azul` (información). Con ícono a la izquierda y la acción a seguir.

## Dinero, cantidades y fechas
- Montos en $ con su equivalente en Bs.: `<x-precio :usd="$venta->total" :ves="$venta->total_ves" />`
  (en operaciones guardadas pasa la tasa o el monto en Bs. de ese momento, no la de hoy).
- Texto suelto: `@usd($monto)`, `@ves($monto)`, `@bs($usd)` o `\App\Support\Moneda::usd()`. Nunca `number_format` a mano.
- Cantidades y existencias: siempre con `@cantidad($x)` (o `\App\Support\Cantidad::formato()` dentro de PHP o
  tras una letra, como en "x3"), con la unidad del producto. Nunca la columna cruda (saldría "3.000").
  Campos de cantidad: ver skill `formularios` (enteros salvo kg, lb, litro).
- Fechas: `->format('d/m/Y')`; con hora `->format('d/m/Y h:i A')` (12 h). Relativas ("hace 2 horas") solo en líneas de tiempo.
- Cifras alineadas a la derecha y `whitespace-nowrap` en tablas.

## Roles en la vista
- Mostrar/ocultar con `auth()->user()->esDueno()` o `esEncargadoOSuperior()`. Ocultar un botón no protege:
  la ruta también debe estar en el grupo `role:` correcto.

## Accesibilidad básica
- `<label for>` ligado al `id` de cada campo; botones de solo ícono con `title` + `aria-label`.
- Contraste: texto secundario mínimo `stone-500` en claro / `stone-400` en oscuro.
- Áreas táctiles de al menos `p-2` (36 px); nada que dependa solo del color (acompaña con texto o ícono).
- Enlaces externos `target="_blank" rel="noopener"`.

## Impresión y PDF
- Lo que no debe salir impreso lleva `no-print` (botones, buscadores, navegación).
- PDF con dompdf (`*/pdf.blade.php`): HTML simple, estilos en línea o `<style>`, sin Tailwind ni JS ni fuentes externas.

## Revisión antes de terminar
Abre la pantalla con el preview `laravel` (skill `probar-cambios`) y comprueba: claro y oscuro, 375 px,
tabla vacía y con datos, un rol sin permisos, y consola sin errores. Pasa también la skill `antislop`.
