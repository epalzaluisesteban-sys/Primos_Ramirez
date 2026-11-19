---
name: caja-diaria
description: Reglas de la caja diaria de Los Primos Ramírez - una sola caja por día, no vender sin cerrar la del día anterior, gastos del día que se restan al cierre, reporte de cierre y ticket del día en PDF. Úsala siempre que toques CajaController, CierreCaja, gastos, ventas o abonos que dependan de la caja, o el reporte de cierre.
---

# Caja diaria

La caja es el cuadre del efectivo de **un día**. Todo lo que entra o sale del cajón ese día tiene que caer en
la caja de esa fecha, y una caja sin cerrar bloquea el día siguiente.

## Invariantes (no romperlas)
1. **Una sola caja por fecha.** Se garantiza en dos niveles:
   - BD: índice único en `cierre_cajas.fecha`.
   - Código: `abrir()` revisa antes de crear y captura `UniqueConstraintViolationException` por si dos envíos
     llegan a la vez. Nunca confiar solo en el `if` del controlador: un doble clic en "Abrir caja" crea dos filas.
2. **Una sola caja ABIERTA en todo el sistema.** Antes de abrir la de hoy se busca cualquier caja `ABIERTA`
   de una fecha anterior (`CierreCaja::pendienteAnterior()`). Si existe, no se abre la de hoy ni se vende:
   se manda a cerrarla con `CierreCaja::avisoCajaPendiente()` ("La caja del dd/mm/aaaa quedó abierta…").
3. **La pantalla de caja debe poder cerrar una caja vieja.** Si `pendienteAnterior()` devuelve algo, el panel
   muestra el formulario de cierre de **esa** caja (no el de hoy). Sin esto la caja vieja queda abierta para
   siempre y aparecen "dos cajas abiertas" en el historial.
4. **El cierre se calcula con la fecha de la caja**, no con `now()` (`CajaService::resumen($caja->fecha)`).
5. **Efectivo esperado por moneda** = fondo inicial + ventas de contado + abonos − **gastos** − **pagos a
   proveedores hechos con efectivo de caja** (`pagos_proveedor.cierre_caja_id` no nulo). `resumen()` da cada parte y
   `salidas_usd/ves` = gastos + pagos; eso es lo que se guarda en `monto_gastos` al cerrar ("Gastos y pagos").
   Cada gasto o pago guarda su moneda (`USD`/`VES`), monto y tasa, igual que los abonos.
6. Ventas, abonos y gastos solo con la caja de hoy abierta (`CierreCaja::estaAbierta(now())`); con la caja
   cerrada no entran ni se editan.

## Gastos del día
- Tabla `gastos`: `cierre_caja_id` (FK restrict), `user_id`, `concepto` (requerido, máx. 150), `categoria`
  (`Gasto::CATEGORIAS`), `moneda`, `monto_moneda` (lo que salió del cajón), `monto` (equivalente en USD), `tasa_cambio`,
  `fecha`. Está en `RespaldoService::TABLAS` y en `TABLAS_POSTERIORES` (un respaldo viejo sin gastos se restaura igual).
- Se anotan desde la pantalla de Caja (`GastoController`, todos los roles). Quitar: Encargado/Dueño.
- Los cálculos viven en `App\Services\CajaService` (`resumen`, `esperado`, `reporte`); no repetirlos en controladores.
- Un gasto no puede dejar el esperado en negativo: validar contra lo que hay en el cajón en esa moneda.
- En la interfaz "Eliminar gasto" solo mientras la caja siga abierta.

## Reporte de cierre ("todo lo que se hizo en el día")
Al cerrar se ofrece descargar el PDF del día (`caja.reporte`, `caja/reporte-pdf.blade.php`, extiende `pdf.layout`):
- Encabezado: fecha, quién abrió, quién cerró, hora de apertura y cierre, tasa BCV del día.
- Cuadre por moneda: fondo, contado, abonos, gastos, esperado, contado real, diferencia.
- Ventas del día (sin anuladas): número, hora, cliente, tipo de pago, total $ y Bs.
- Productos vendidos agrupados (producto, unidad, cantidad con `Cantidad::formato()`, total).
- Fiados del día y abonos recibidos (cliente, monto, moneda).
- Gastos (concepto, categoría, monto).
- Ventas anuladas del día en una sección aparte, solo informativa.
`CajaService::reporte()` alimenta el PDF y el ticket de 80 mm (`caja.ticket`, `caja/ticket.blade.php`).

## Pruebas obligatorias (`tests/Feature/CajaTest.php`)
- No se abre una segunda caja el mismo día (incluido el caso de dos POST seguidos).
- Con la caja de ayer abierta: no se abre la de hoy, no se registra venta ni abono, y la pantalla ofrece cerrarla.
- El cierre resta los gastos por moneda.
- Reabrir solo la de hoy (regla que ya existe) sigue funcionando.
- El reporte de cierre no incluye ventas anuladas.

Si cambia una regla, actualiza el capítulo de Caja en `resources/views/ayuda/` y la sección "Caja diaria" de `CLAUDE.md`.
