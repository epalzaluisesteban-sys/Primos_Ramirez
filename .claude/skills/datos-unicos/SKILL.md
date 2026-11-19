---
name: datos-unicos
description: Cómo evitar datos duplicados y validar todos los campos en Los Primos Ramírez - índices únicos en la BD, reglas en el controlador que también miran los desactivados, normalizar antes de comparar (cédula/RIF, nombres, códigos), corrección de repetidos viejos sin borrar y mensajes claros. Úsala al crear o cambiar cualquier tabla, formulario o validación de clientes, productos, proveedores, categorías o usuarios.
---

# Datos únicos y validación completa

Regla: **un mismo dato del mundo real no puede quedar registrado dos veces**, y **ningún campo llega a la BD sin
validar**. La regla del controlador da el mensaje amable; el índice único de la BD es la garantía.

## Qué es único
| Tabla | Único | Cómo |
|---|---|---|
| `productos` | nombre + marca, por `unidad_medida`; `codigo_barras` | índice (`nombre_normalizado`, `unidad_medida`) y `codigo_barras` |
| `categorias` | nombre | índice `nombre_normalizado` |
| `proveedores` | nombre; `rif` | índices `nombre_normalizado` y `rif` |
| `clientes` | `rif` (cédula/RIF unido, ej. `V-12345678`) | índice `rif`. El nombre **sí** se repite (dos "José López") |
| `users` | `email` | |
| `cierre_cajas` | `fecha` | ver skill `caja-diaria` |

"Tomate" por kg y por caja son dos productos. `null` se permite en `rif` y `codigo_barras` (sin dato).
Los desactivados **también cuentan**: el mensaje lo dice con `avisoSiInactivo()` ("Está desactivado: actívalo…").

## Piezas que ya existen (no rehacerlas)
- `App\Support\Unicidad`: `normalizar()` ("  Tomáte " → "tomate"), `claveProducto()`, y `corregir($tabla, $filas)`,
  que arregla repetidos guardados sin borrar: nombre → "Mora (repetido 2)", código de barras repetido → null,
  cédula/RIF repetido → null y anotado en `notas`. Lo usan la migración `datos_unicos` y la restauración de respaldos.
- Trait `Models\Concerns\TieneNombreUnico` (Producto, Categoria, Proveedor): limpia espacios del nombre y llena
  `nombre_normalizado` al guardar; `Modelo::conClave($clave, $ignorarId, $donde)` busca el repetido.
  Producto sobrescribe `claveNombre()` para sumar la marca.
- `Contacto::reglasDocumento($otroDueno)` + `Contacto::limpiarNumeroDocumento()` y el componente
  `<x-campo-documento :valor="$modelo->rif ?? null" />` (clientes y proveedores).

## Reglas en el controlador
- Regla con closure sobre el campo, comparando la clave normalizada e ignorando el registro que se edita:
  ```php
  function ($atributo, $valor, $falla) use ($proveedor) {
      if ($otro = Proveedor::conClave(Unicidad::normalizar($valor), $proveedor?->id)) {
          $falla("Ya hay un proveedor llamado «{$otro->nombre}»." . $otro->avisoSiInactivo());
      }
  }
  ```
- El mensaje nombra el registro que ya existe y dice qué hacer ("Búscalo en la lista…").
- El alta rápida de clientes desde la venta usa la misma validación (`validarCliente()`).
- No hace falta `try/catch` de `UniqueConstraintViolationException` en formularios normales (el layout deshabilita
  el botón al enviar). Solo donde un doble envío es probable y no hay formulario de por medio (ej. abrir caja).

## Tabla nueva con un dato único
1. Migración nueva (nunca editar una vieja). Si la tabla ya tiene datos, corrige los repetidos con una regla que no
   borre (agrega el caso a `Unicidad::corregir()`), luego crea el índice. Render corre `migrate` en cada despliegue y
   se detiene si falla: la migración no puede lanzar excepción por datos viejos.
2. Si la columna es de un nombre, usa el trait `TieneNombreUnico`.
3. Prueba en `tests/Feature/DatosUnicosTest.php`.

## "Todos los campos validados" (lista de revisión por formulario)
- Cada `name=` del formulario tiene su regla en el controlador; nada se guarda con `$request->all()` ni `except()`.
- Texto: `string|max:N` igual al largo de la columna (y `min:2` en nombres). Requeridos con `required` en HTML y PHP.
- Números: `numeric|min:0` (o `gt:0` para cantidades y precios de venta), con máximo (`max:99999999`).
- Fechas: `date`, y `before_or_equal:today` donde no tenga sentido el futuro (compras, mermas).
- Selects: `Rule::in(...)` o `exists:tabla,id` filtrando `activo`.
- Correo `email`, teléfono `Contacto::REGLA_TELEFONO`, cédula `Contacto::reglasDocumento()`.
- Cada campo con `id`, `<label for>` y `@error` debajo (skill `formularios`). El HTML repite los límites
  (`maxlength`, `min`, `step`, `required`), pero la regla que manda es la del servidor.

## Pruebas (`tests/Feature/DatosUnicosTest.php`)
Por cada dato único: el segundo igual (también con mayúsculas, tildes o espacios distintos) falla con el mensaje;
editar sin cambiar el dato pasa; el desactivado también bloquea; la BD rechaza el repetido aunque no pase por el
formulario.
