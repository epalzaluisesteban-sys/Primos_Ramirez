# Los Primos Ramírez — guía del proyecto

Sistema de gestión (Laravel 12 + Blade + Tailwind 4) para una distribuidora de frutas y verduras en Venezuela:
ventas de contado y fiado, caja diaria, inventario con kardex, compras, proveedores, mermas, clientes,
reportes y tasa BCV. Todo el texto de la interfaz y del código (nombres, comentarios, mensajes) está **en español**.

## Cómo correrlo y probarlo

```bash
composer install && npm install && npm run build
php artisan migrate              # local: MySQL de XAMPP (DB_CONNECTION=mysql)
php artisan storage:link         # enlace public/storage para las fotos (si falta, Laravel las sirve igual)
php artisan serve                # http://127.0.0.1:8000  (o preview "laravel" de .claude/launch.json)
php artisan test                 # SQLite en memoria (phpunit.xml)
```

- Producción: Back4app Containers (con el `Dockerfile`) + Supabase (PostgreSQL y almacenamiento S3). Ver `DESPLIEGUE.md`.
  El puerto, los procesos de Apache y la memoria de PHP se ajustan por variables en `docker/entrypoint.sh`.
- `App\Support\SqlFecha` solo genera SQL para **MySQL y PostgreSQL**: el Dashboard, Reportes y Estadísticas
  no funcionan en SQLite, así que las pruebas no deben renderizar esas pantallas.

## Mapa del código

| Qué | Dónde |
|---|---|
| Rutas y permisos por rol | `routes/web.php` (grupos `role:Dueno,Encargado` y `role:Dueno`) |
| Menú (definido una sola vez para escritorio y móvil) | `resources/views/partials/nav.blade.php` → arreglo `$menu`; bajo 1280 px es un panel lateral (`#menu-movil`) con scroll propio |
| Notificaciones de la campana y tasa en el nav | `AppServiceProvider` (View Composer de `partials.nav`); los avisos de productos enlazan a su filtro |
| Alertas de stock (agotados, stock bajo, vencimiento, conviene comprar) | Filtros de Productos (`/productos?filtro=...`) con `App\Services\AlertasInventarioService` |
| Layout, avisos (toast), modal de confirmación, loader | `resources/views/layouts/app.blade.php` |
| Íconos (SVG propios, sin emojis) | `<x-icon name="..."/>` en `components/icon.blade.php` |
| Encabezado de cada página (título, subtítulo, volver, acciones) | `<x-encabezado>` en `components/encabezado.blade.php` |
| Moneda | `App\Support\Moneda`, directivas `@usd()`, `@ves()`, `@bs()` y `<x-precio>` |
| Fotos (local o S3) | `App\Support\Almacen`; el disco `public` tiene `serve` activo en `config/filesystems.php` para que se vean aunque falte el enlace |
| Teléfonos y cédula/RIF (limpiar, validar, mostrar) | `App\Support\Contacto`, `<x-campo-telefono>` |
| Campos de contraseña (con botón para verla) | `<x-campo-clave>`; el JS `alternarClave()` está en el layout |
| Guía "Cómo usar" (menú del avatar, todos los roles) | `resources/views/ayuda/`: capítulos en `index.blade.php`, una pantalla de muestra por parcial y la animación/práctica en `_script.blade.php`. Todo en memoria del navegador: **sin `<form>` ni `name=`** (lo vigila `GuiaUsoTest`). Si cambia una regla del negocio, actualiza su capítulo |
| Línea de tiempo (Dashboard e Historial) | `App\Services\ActividadService` |
| Exportar CSV | `Controller::descargarCsv()`; PDF con `barryvdh/laravel-dompdf` (`*/pdf.blade.php`) |
| Respaldo y restauración (solo Dueño, menú Administración) | `App\Services\RespaldoService` (lista fija `TABLAS` en orden de dependencias), `RespaldoController`, middleware `BloquearDuranteRestauracion` |

**Respaldos:** un solo `.json` de texto plano (formato 2: datos por tabla y, al final, las fotos en base64),
independiente del motor (MySQL ↔ PostgreSQL). Al restaurar también se aceptan los `.tar.gz` del formato 1; el tipo se
reconoce por el contenido. Se guardan en el disco de `RespaldoService::disco()` (`RESPALDOS_DISK`): `local`
(`storage/app/private/respaldos`) en la PC y el bucket privado `respaldos` de Supabase en producción, nunca en el de fotos.
Los archivos subidos para restaurar quedan siempre en `local` (`DISCO_PENDIENTES`).
**Si agregas una tabla con datos del negocio, súmala a `RespaldoService::TABLAS`** (después de las que referencia) y,
si guarda fotos, a `COLUMNAS_FOTO`; si no, no entrará en los respaldos. Restaurar hace antes un respaldo automático.

## Roles

- **Dueno** (sin tilde en BD): todo, incluido eliminar registros y gestionar personal.
- **Encargado**: ventas, caja, inventario, compras, clientes, proveedores, reportes.
- **Obrero**: registrar ventas, abrir/cerrar caja, ver sus ventas.

Comprobar con `esDueno()`, `esEncargadoOSuperior()` o el middleware `role:`; nunca comparar strings sueltos en vistas.

## Reglas de negocio (no romperlas)

- **Moneda base: USD.** Bs. = USD × tasa BCV. Cada venta guarda `tasa_cambio` y `total_ves`; cada abono guarda moneda, monto en esa moneda y tasa.
- **Ventas anuladas** no cuentan en caja, reportes, estadísticas, kardex ni saldo de clientes. Las ventas nunca se borran.
- **Caja diaria:** efectivo esperado = fondo inicial + ventas de **contado** + **abonos** del día (por moneda).
  Solo se registran ventas y abonos con la caja de **hoy abierta** (`CierreCaja::estaAbierta()`; si no, se manda a abrirla).
  Con la caja del día **cerrada** no se registran ventas ni abonos y no se editan/anulan ventas (`CierreCaja::estaCerrada()`).
  El cierre se calcula con la fecha de la caja, no con "hoy".
  Encargado o Dueño pueden **reabrir** la caja de hoy (`caja.reabrir`); el cierre anterior queda anotado en `observaciones`.
- **Saldo de cliente** = ventas a crédito no anuladas − abonos. Respetar `limite_credito` si existe.
  **Crédito disponible** = límite − saldo, nunca negativo; `null` = sin límite (`Cliente::creditoDisponible()`).
- **Stock:** solo cambia por Compras (+), Ventas (−), Mermas (−) o `AjusteInventario::registrar()` (±, con motivo).
  Nunca asignar `stock` directo: el kardex debe cuadrar con el stock del producto.
- **Costo** = promedio ponderado de compras (`Compra::costosPromedio()`). Lo vendido de productos **sin compras**
  (costo desconocido) no entra en la ganancia ni en el margen (Dashboard y Reporte de Ganancias): se avisa aparte.
- **Historial protegido:** las llaves foráneas son `RESTRICT`. Usuarios, productos, clientes y proveedores con
  movimientos se **desactivan** (`activo = false`), no se eliminan. Productos inactivos no se venden ni se compran.
- Cantidades con hasta 3 decimales solo en productos por peso o volumen (kg, lb, litro). Los que se cuentan
  (`Producto::UNIDADES_ENTERAS`: unidad, caja, atado, saco) van **sin decimales** en ventas, compras, mermas y existencias
  (`Producto::errorCantidad()`; en las vistas `data-entero` + `data-ayuda` en cada `<option>` y
  `ajustarCantidadSegunOpcion()` del layout). La regla la pone **la unidad del producto elegido**; sin producto no se
  restringe y bajo el campo se explica (`Producto::ayudaCantidad()`). Para **mostrar** cantidades/existencias usar `@cantidad($x)` o `App\Support\Cantidad::formato()`
  (3.000 → "3", 2.500 → "2.5"), nunca imprimir la columna cruda.

## Convenciones

- Controladores: validar con `$request->validate()`, operaciones de stock/dinero dentro de `DB::transaction` con
  `lockForUpdate()`, y responder `redirect()->...->with('success', '...')` o `back()->withErrors(['error' => '...'])->withInput()`.
- Mensajes al usuario en español sencillo, diciendo qué hacer ("Desactívalo en su lugar…").
- Formularios: `@csrf`, `old('campo', $modelo->campo ?? '')`, `data-confirm="..."` en acciones destructivas.
- Vistas compartidas crear/editar en `_form.blade.php`. Colores: paleta verde/naranja/rojo con `stone` de base y modo oscuro (`dark:`).
- **Piezas de interfaz** en `resources/css/app.css` (ya traen modo oscuro); úsalas en vez de copiar clases largas:
  `pagina`, `pagina-angosta`, `titulo-pagina`, `subtitulo-pagina`, `titulo-seccion`, `tarjeta`, `titulo-tarjeta`,
  `cifra-etiqueta`, `cifra-valor`, `menu-desplegable`, `caja-suave`, `etiqueta`, `campo`, `campo-archivo`,
  `casilla`, `ayuda-campo`, `error-campo`, `btn-primario|secundario|oscuro|peligro` (+ `btn-sm`),
  `btn-icono-ambar|rojo|verde|gris`, `insignia-verde|naranja|roja|ambar|azul|violeta|gris`, `aviso-ambar|azul|rojo`,
  `tabla` (+ `tabla-vacia`). Se ajustan con utilidades normales (`tarjeta p-4`). Tras cambiar `app.css`: `npm run build`.
- Los avisos de éxito/error ya los muestra el layout como toast: las vistas no deben repetir `session('success')` ni `$errors->any()`.
- Cada arreglo de lógica de negocio lleva su prueba en `tests/Feature` (ver `IntegridadHistorialTest`).

## Skills del proyecto

En `.claude/skills/`:
- `estructura`: dónde va cada cosa (capas, componentes vs parciales, nombres). Antes de crear archivos.
- `interfaz`: patrones de pantallas (listado, crear/editar, detalle), piezas de `app.css`, modo oscuro, móvil.
- `formularios`: campos, validación, errores, montos en $ y Bs., accesibilidad. Siempre que toques un `<form>`.
- `agregar-modulo`: pasos para un módulo o CRUD nuevo de punta a punta.
- `probar-cambios`: pruebas y verificación en el navegador antes de dar algo por terminado.
- `antislop`: filtro contra texto, diseño y código genérico de IA (relleno, emojis, degradados, comentarios
  obvios, try/catch que tragan errores, archivos de resumen). **Obligatoria** antes de dar por terminado
  cualquier cambio en vistas, textos, CSS o lógica.
