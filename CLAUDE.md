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

- Producción: Render + Supabase (PostgreSQL y almacenamiento S3). Ver `DESPLIEGUE.md` y `render.yaml`.
- Render corre **PHP 8.3** (`Dockerfile`: `php:8.3-apache`) y la PC puede tener otra versión: `composer.json` fija
  `config.platform.php = 8.3.0` para que `composer update` no traiga paquetes que exijan un PHP más nuevo (el Dockerfile
  instala con `--ignore-platform-reqs` y no lo detectaría). Si se sube la versión de PHP en el Dockerfile, cambia ambos.
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
| Teléfonos y cédula/RIF (limpiar, validar, mostrar) | `App\Support\Contacto` (`reglasDocumento()`), `<x-campo-telefono>`, `<x-campo-documento>` |
| Datos únicos (no repetir productos, categorías, proveedores ni cédulas) | `App\Support\Unicidad`, trait `Models\Concerns\TieneNombreUnico` (columna `nombre_normalizado` con índice único) |
| Recuperar contraseña ("¿Olvidaste tu contraseña?" del login) | `RecuperarClaveController` (broker de Laravel, rutas `password.*`), correo `Notifications\RestablecerClave`, textos en `lang/es/passwords.php`. En Render el correo sale desde Gmail por un Apps Script (`MAIL_MAILER=gmail`, transporte `App\Support\CorreoGmail`, código del script en `DESPLIEGUE.md` 3.3) porque el plan gratis bloquea SMTP y no hay dominio propio. Sin URL/secreto, o con `log` en producción, no ofrece el enlace y manda a pedírselo al Dueño |
| Campos de contraseña (con botón para verla) | `<x-campo-clave>`; el JS `alternarClave()` está en el layout |
| Guía "Cómo usar" (menú del avatar, todos los roles) | `resources/views/ayuda/`: capítulos en `index.blade.php`, una pantalla de muestra por parcial y la animación/práctica en `_script.blade.php`. Todo en memoria del navegador: **sin `<form>` ni `name=`** (lo vigila `GuiaUsoTest`). Si cambia una regla del negocio, actualiza su capítulo |
| Línea de tiempo (Dashboard e Historial) | `App\Services\ActividadService` |
| Caja: cuadre, reporte del día (PDF) y ticket del cierre | `CajaService`, `CajaController`, `caja/reporte-pdf`, `caja/ticket` |
| Gastos (pantalla aparte en Ventas → Gastos: anotar, registro con filtros, CSV y PDF) | `GastoController`, `gastos/index`, `gastos/pdf`; la caja solo muestra el resumen del día |
| Ventanas flotantes (formularios "Nuevo…" y fichas) | `<x-ventana id>` + botón `data-abrir-ventana="id"`; el form lleva `<input type="hidden" name="_ventana" value="id">` para reabrirse con errores. Fichas: `<tr data-ficha="url" data-titulo>` y el controlador responde un parcial `_ficha`/`_detalle` si `$request->ajax()` (ventas, productos, clientes, proveedores, caja, categorías, usuarios, compras, pérdidas, gastos; también las filas de venta o compra del Inicio, kardex, cuenta del cliente y del proveedor). Botones de la fila dentro de `data-acciones`. **Editar** también va en la ventana: enlace `<a href="edit" data-editar>` y `edit()` responde el parcial `_editar` (`<x-form-editar>` + `_form`) si `$request->ajax()`; la pantalla `edit` queda de respaldo. Con errores se reabre sola (`_editar_en` + middleware `ReabrirEdicionEnVentana`, que además deja limpio el "Nuevo…" de esa pantalla). Al cargarse, los `id` del formulario pasan a `vf-…`: el JS de un `_form` busca dentro de su `<form>` (`document.currentScript.closest('form')`), nunca por id. Ventas se edita en su pantalla (carrito); compras, pérdidas y gastos no se editan. Script en el layout |
| Filtros y tablas en el celular | `<x-filtros id :activos>` pliega el formulario bajo un botón; columnas secundarias con `hidden sm:table-cell` (la ficha muestra el resto) |
| Proveedores: lo que distribuyen, crédito, pagos y "Lo que debemos" | `ProveedorController` (`deudas`, `deudasPdf`), `PagoProveedorController`, `Proveedor::estadoDeuda()`, `proveedores/deudas` |
| Exportar CSV y PDF | `Controller::descargarCsv()`; PDF con `barryvdh/laravel-dompdf` (`*/pdf.blade.php`, layout `pdf.layout`) siempre por `Controller::entregarPdf()` (`?ver=1` lo abre para imprimir, sin él se descarga). `<x-acciones-exportar>` pone Excel, PDF e Imprimir: **imprimir nunca es `window.print()` de la pantalla** |
| Tickets de 80 mm (venta y cierre de caja) | PDF con layout `pdf.ticket` y `Controller::entregarTicket()` (alto según las líneas) |
| Etiqueta del producto (código de barras y QR) | `App\Support\Etiqueta` (SVG en el servidor con `picqer/php-barcode-generator` y `chillerlan/php-qrcode`, sin GD); vista del kardex y PDF de 100 × 45 mm en `productos.etiqueta` |
| Filtros de reportes | `?categoria_id=` en productos, ventas y ganancias (`<x-selector-categoria>`); `?estado=con_deuda|sin_deuda` en clientes. La pantalla, el CSV y el PDF usan el mismo método de datos |
| Respaldo y restauración (solo Dueño, menú Administración) | `App\Services\RespaldoService` (lista fija `TABLAS` en orden de dependencias), `RespaldoController`, middleware `BloquearDuranteRestauracion` |

**Respaldos:** un solo `.json` de texto plano (formato 2: datos por tabla y, al final, las fotos en base64),
independiente del motor (MySQL ↔ PostgreSQL). Al restaurar también se aceptan los `.tar.gz` del formato 1; el tipo se
reconoce por el contenido. Se guardan en el disco de `RespaldoService::disco()` (`RESPALDOS_DISK`): `local`
(`storage/app/private/respaldos`) en la PC y el bucket privado `respaldos` de Supabase en Render, nunca en el de fotos.
Los archivos subidos para restaurar quedan siempre en `local` (`DISCO_PENDIENTES`).
**Si agregas una tabla con datos del negocio, súmala a `RespaldoService::TABLAS`** (después de las que referencia) y,
si guarda fotos, a `COLUMNAS_FOTO`; si no, no entrará en los respaldos. Restaurar hace antes un respaldo automático.

## Roles

- **Dueno** (sin tilde en BD): todo, incluido eliminar registros y gestionar personal.
- **Encargado**: ventas, caja, inventario, compras, clientes, proveedores, reportes.
- **Obrero**: registrar ventas, abrir/cerrar caja, ver sus ventas y entrar a Clientes (lista y ficha) para cobrar
  abonos de los fiados. No crea, edita ni exporta clientes (esas rutas siguen en `role:Dueno,Encargado`).

Comprobar con `esDueno()`, `esEncargadoOSuperior()` o el middleware `role:`; nunca comparar strings sueltos en vistas.

## Reglas de negocio (no romperlas)

- **Moneda base: USD.** Bs. = USD × tasa BCV. Cada venta guarda `tasa_cambio` y `total_ves`; cada abono guarda moneda, monto en esa moneda y tasa.
- **Ventas anuladas** no cuentan en caja, reportes, estadísticas, kardex ni saldo de clientes. Las ventas nunca se borran.
  En la interfaz "Eliminar venta" = anularla (`ventas.anular`, guarda `anulada_por`) y la venta pasa a la **Papelera**
  (`ventas.papelera`, Encargado o Dueño). Restaurar (`Venta::puedeRestaurarse()`) solo si es de hoy con la caja abierta,
  vuelve a descontar el stock y revisa el límite de crédito. El listado de ventas y su CSV no muestran las anuladas.
- El cliente es opcional en ventas de contado (queda en su ficha, no suma deuda) y obligatorio en fiado. Desde la venta
  se registra un cliente nuevo por fetch (`clientes.rapido`, todos los roles, sin límite de crédito).
- **Caja diaria:** efectivo esperado = fondo inicial + ventas de **contado** + **abonos** − **gastos** del día (por moneda),
  − **pagos a proveedores con efectivo de caja**, calculado en `App\Services\CajaService` (pantalla, cierre, gastos,
  reporte PDF y ticket del día).
  **Una caja por fecha** (índice único en `cierre_cajas.fecha`). Si quedó abierta la caja de un día anterior
  (`CierreCaja::pendienteAnterior()`), no se abre la de hoy ni se registran ventas, abonos o gastos hasta cerrarla.
  Solo se registran ventas, abonos y gastos con la caja de **hoy abierta** (`CierreCaja::estaAbierta()`; si no, se manda a abrirla).
  Gastos: pantalla propia (`gastos.index`), cualquier rol los anota y ve su registro; solo Encargado/Dueño los quitan
  (solo con la caja abierta) y exportan el reporte.
  Con la caja del día **cerrada** no se registran ventas ni abonos y no se editan/anulan ventas (`CierreCaja::estaCerrada()`).
  El cierre se calcula con la fecha de la caja, no con "hoy".
  Encargado o Dueño pueden **reabrir** la caja de hoy (`caja.reabrir`); el cierre anterior queda anotado en `observaciones`.
- **Proveedores y lo que se les debe:** cada proveedor marca las categorías que distribuye (`categoria_proveedor`) y si
  da crédito (`da_credito`, `dias_credito`). Una compra es `contado` o `credito`; a crédito solo con proveedor registrado
  que da crédito, y lleva `vence_el` = hoy + días. Deuda = compras a crédito − pagos (`PagoProveedor`), nunca un pago
  mayor a la deuda; los pagos cubren primero las compras más viejas (`Proveedor::estadoDeuda()`). Un pago con
  efectivo de caja (`cierre_caja_id`) necesita la caja de hoy abierta y se resta del cuadre. Pantalla `proveedores.deudas`.
- **Saldo de cliente** = ventas a crédito no anuladas − abonos. Respetar `limite_credito` si existe.
  **Crédito disponible** = límite − saldo, nunca negativo; `null` = sin límite (`Cliente::creditoDisponible()`).
- **Stock:** solo cambia por Compras (+), Ventas (−), Mermas (−) o `AjusteInventario::registrar()` (±, con motivo).
  Nunca asignar `stock` directo: el kardex debe cuadrar con el stock del producto.
- **Costo** = promedio ponderado de compras (`Compra::costosPromedio()`). Lo vendido de productos **sin compras**
  (costo desconocido) no entra en la ganancia ni en el margen (Dashboard y Reporte de Ganancias): se avisa aparte.
- **Historial protegido:** las llaves foráneas son `RESTRICT`. Usuarios, productos, clientes y proveedores con
  movimientos se **desactivan** (`activo = false`), no se eliminan. Productos inactivos no se venden ni se compran.
- **Sin duplicados** (índice único en BD + regla en el controlador, también contra los desactivados; mayúsculas,
  tildes y espacios no cuentan): producto = nombre + marca por unidad de medida, y código de barras; categoría y
  proveedor por nombre; cédula/RIF de clientes y de proveedores. Los repetidos viejos (migración o respaldo
  anterior) los corrige `Unicidad::corregir()` sin borrar: "(repetido 2)" en el nombre o el documento pasa a las notas.
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
  **Todo campo validado** en el servidor (texto con `max`, números con `min` y `max`, fechas, `in`/`exists`), y en la vista
  `id` + `<label for>` + `@error` debajo + los mismos límites en HTML. Mensajes en `lang/es/validation.php`: si agregas
  un campo, suma su nombre legible en `attributes`. Los correos se guardan en minúsculas (`User::normalizarCorreo()`).
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
- `caja-diaria`: una caja por día, cerrar la anterior antes de vender, gastos del día y reporte de cierre.
- `datos-unicos`: índices únicos, `Rule::unique`, normalizar antes de comparar y validar todos los campos.
- `reportes-pdf`: reportes con datos compartidos pantalla/CSV/PDF, filtros, tickets e imprimir como PDF.
- `ui-pixel-auditor`: auditoría visual detallada (alineación de campos, espaciados, bordes, desbordes y móvil).
  Al revisar diseños, maquetación, CSS o formularios.
- `antislop`: filtro contra texto, diseño y código genérico de IA (relleno, emojis, degradados, comentarios
  obvios, try/catch que tragan errores, archivos de resumen). **Obligatoria** antes de dar por terminado
  cualquier cambio en vistas, textos, CSS o lógica.
