---
name: estructura
description: Dónde va cada cosa en Los Primos Ramírez (controladores, modelos, Support, Services, traits, componentes Blade, parciales, rutas, CSS/JS, pruebas) y cómo nombrarla. Úsala antes de crear un archivo nuevo, al mover lógica entre capas o al decidir si algo es componente, parcial o helper.
---

# Estructura del código

Laravel 12 monolito con Blade + Tailwind 4 (Vite). Sin SPA, sin Livewire/Alpine: JS mínimo en el layout.
Todo se nombra en **español** (clases, métodos, variables, rutas, tablas). Antes de crear algo, busca si ya existe.

## Capas y responsabilidades
| Capa | Carpeta | Qué va ahí | Qué NO va |
|---|---|---|---|
| Rutas | `routes/web.php` | Rutas agrupadas por rol (`role:Dueno,Encargado`, `role:Dueno`) | Lógica |
| Controladores | `app/Http/Controllers/<Modelo>Controller.php` | Validar (`$request->validate`), orquestar, `DB::transaction`, responder redirect/vista | Consultas largas repetidas, formateo de dinero |
| Modelos | `app/Models` | `$fillable`, `$casts`, relaciones en español, scopes, reglas del propio registro (`CierreCaja::estaCerrada()`, `Compra::costosPromedio()`, `AjusteInventario::registrar()`) | HTML, request |
| Traits de modelo | `app/Models/Concerns` | Comportamiento compartido entre modelos (ej. `TieneFoto`) | |
| Servicios | `app/Services` | Procesos que cruzan varios modelos o llaman APIs (`ActividadService`, `TasaCambioService`) | |
| Apoyo | `app/Support` | Utilidades estáticas sin estado: `Moneda` (formato y conversión), `Almacen` (fotos local/S3), `SqlFecha` (SQL MySQL/PostgreSQL) | Acceso a request |
| Proveedor | `app/Providers/AppServiceProvider.php` | Directivas Blade (`@usd`, `@ves`, `@bs`), View Composer del nav | |

Regla para decidir: si una consulta o cálculo se usa en **dos** controladores, súbelo al modelo (scope o método
estático) o a un Service. Si es formato de presentación, a `Support` o a un componente Blade.

## Vistas (`resources/views`)
- Una carpeta por módulo en plural (`proveedores/`), archivos `index`, `create`, `edit`, `show`, `_form`, `pdf`.
- Parciales con guion bajo (`_form`, `_fila`) para trozos de **un solo módulo**: `@include('ventas._fila', [...])`.
- Componentes en `components/` (`<x-nombre>`) solo para piezas usadas en **varios módulos** y con props:
  `icon`, `precio`, `acciones-exportar`, `tasa-badge`, `logo`. Declara `@props([...])` con valores por defecto
  y usa `$attributes->merge(['class' => ...])`.
- Layout único `layouts/app.blade.php` (toast, modal `data-confirm`, loader, `filtrarTabla`, `mostrarToast`,
  `alternarTema`). Pilas disponibles: `@stack('head')` y `@stack('scripts')`.
- Menú: solo el arreglo `$menu` de `partials/nav.blade.php` (sirve para escritorio y móvil).
- Clases visuales compartidas: `resources/css/app.css` (`@layer components`). Ver skill `interfaz`.

## Nombres
- Modelo singular (`Proveedor`), tabla plural (`proveedores`), controlador `ProveedorController`.
- Rutas con nombre `modulo.accion` (`proveedores.index`, `proveedores.activo`); si el singular en español es
  irregular, `Route::resource(...)->parameters(['proveedores' => 'proveedor'])`.
- Relaciones: `usuario()` para `user_id`, `producto()`, `detalles()`. Booleanos `activo`, `anulada`.
- Métodos que preguntan empiezan en `es`/`esta`/`tiene` (`esDueno()`, `estaCerrada()`); los que formatean
  describen el resultado (`whatsappUrl()`, `fotoUrl()`).

## Base de datos
- Migración nueva por cambio (nunca editar una ya ejecutada). Llaves foráneas `restrictOnDelete()`.
- Debe funcionar en MySQL (local), PostgreSQL (producción) y SQLite (pruebas): evita SQL crudo; si hace falta
  fecha agrupada usa `SqlFecha` y no lo ejecutes en pruebas.

## Frontend y build
- Entrada Vite: `resources/css/app.css` y `resources/js/app.js`. Tras cambiar CSS o JS: `npm run build`.
- JS pequeño y en español dentro de la vista con `@push('scripts')`; si se reutiliza, al layout o a `app.js`.
  No agregues librerías JS nuevas sin necesidad.

## Pruebas
- `tests/Feature/<Tema>Test.php` (ej. `IntegridadHistorialTest`, `UsabilidadVentasTest`). Una prueba por regla
  de negocio arreglada o creada. Detalles en la skill `probar-cambios`.

## Al terminar
Si agregaste una carpeta, componente o regla nueva, actualiza la tabla "Mapa del código" de `CLAUDE.md`.
