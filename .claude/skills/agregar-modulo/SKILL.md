---
name: agregar-modulo
description: Pasos para agregar un módulo o pantalla nueva al sistema Los Primos Ramírez (migración, modelo, controlador, rutas con roles, vistas, menú, acciones rápidas y pruebas). Úsala al crear cualquier sección nueva o un CRUD.
---

# Agregar un módulo nuevo

Sigue este orden. Todo en español (nombres de tablas, modelos, rutas, textos).

## 1. Base de datos
- Migración nueva en `database/migrations/` con fecha actual. Nunca edites migraciones ya ejecutadas.
- Llaves foráneas hacia historial (`users`, `productos`, `clientes`, `ventas`…) con `->restrictOnDelete()`, nunca `cascade`.
- Si el registro puede "dejar de usarse", agrega `boolean('activo')->default(true)` en lugar de permitir borrarlo.
- Dinero: `decimal(10,2)` en USD; si guarda Bs., también `tasa_cambio decimal(14,4)`. Cantidades: `decimal(10,3)`.
- Índice en las columnas `fecha` que se filtren en reportes.

## 2. Modelo
- `$fillable` explícito, `$casts` (`'activo' => 'boolean'`, `'fecha' => 'datetime'`, cantidades `'float'`).
- Relaciones con nombres en español (`usuario()`, `producto()`); el usuario que registra se llama `user_id` → `usuario()`.
- Si afecta stock, usa `AjusteInventario::registrar()` o el patrón de Compra/Merma; si es efectivo, revisa `CierreCaja::estaCerrada()`.

## 3. Controlador
- `app/Http/Controllers/<Nombre>Controller.php`, validación con `$request->validate([...])`.
- Operaciones de stock/dinero en `DB::transaction` + `lockForUpdate()`.
- Éxito: `redirect()->route('x.index')->with('success', 'Texto corto.')`.
  Error: `back()->withErrors(['error' => 'Qué pasó y qué hacer.'])->withInput()`.
- Exportaciones: `$this->descargarCsv(...)` y PDF con `Pdf::loadView(...)`.

## 4. Rutas (`routes/web.php`)
- Dentro del grupo de rol correcto: general (todos), `role:Dueno,Encargado`, o `role:Dueno` (eliminar / personal).
- Recursos con `->parameters([...])` si el plural en español no es regular (ej. `proveedores` → `proveedor`).

## 5. Vistas (`resources/views/<modulo>/`)
- `@extends('layouts.app')`, `@section('title', ...)`, contenedor `max-w-7xl mx-auto px-4 py-8`.
- Encabezado: `titulo-pagina` + `subtitulo-pagina` con una frase que explique para qué sirve la pantalla.
- Listado, crear/editar y detalle siguen los patrones de la skill `interfaz` (modelo: `proveedores/`):
  `tarjeta`, `tabla`, `tabla-vacia`, insignias, `btn-icono-*` con `title`.
- Formulario compartido en `_form.blade.php` (ver skill `formularios`).
- Íconos con `<x-icon name="..."/>` (lista en `components/icon.blade.php`; agrega el SVG ahí si falta). Sin emojis.
- Dónde poner cada archivo y cómo nombrarlo: skill `estructura`.

## 6. Navegación
- Agrega la entrada al arreglo `$menu` de `resources/views/partials/nav.blade.php`, en el grupo de la **tarea**
  (Ventas, Inventario, Clientes, Reportes), con etiqueta en forma de acción si es una tarea
  ("Recibir mercancía", "Registrar pérdida") y una línea `'ayuda'` que diga para qué sirve.
  Respeta el rol con `esEncargadoOSuperior()` / `esDueno()`. La barra completa se ve desde 1280 px (`xl:`);
  si agregas un grupo, revisa que quepa a ese ancho.
- Si es una tarea diaria, agrega una tarjeta en "¿Qué quieres hacer?" del inicio (`dashboard.blade.php`, arreglo `$acciones`).

## 7. Pruebas y verificación
- Prueba en `tests/Feature/` con `RefreshDatabase` para cada regla de negocio (ver skill `probar-cambios`).
- Actualiza `CLAUDE.md` si agregaste una regla de negocio nueva.
