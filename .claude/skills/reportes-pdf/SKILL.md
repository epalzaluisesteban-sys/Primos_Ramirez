---
name: reportes-pdf
description: Cómo hacer reportes en Los Primos Ramírez - un método de datos compartido por pantalla, CSV y PDF (dompdf), filtros (fechas, categoría, con deuda / sin deuda), tickets de 80 mm en PDF, y "Imprimir" que abre el PDF en vez de imprimir la pantalla. Úsala al crear o cambiar cualquier reporte, exportación, ticket o el botón de imprimir.
---

# Reportes y PDF

Los tutores pidieron que **imprimir genere un PDF**, no una captura de la pantalla. Todo lo imprimible sale de una
vista PDF hecha para papel. Nunca `window.print()` sobre una pantalla del sistema.

## Patrón de un reporte
1. Un método privado arma los datos a partir del `Request` (filtros incluidos): `ReporteController::datosGanancias()`,
   `VentaController::datosListado()`, `ProductoController::productosFiltrados()`, `ClienteController::datosClientes()`,
   `ProveedorController::datosDeudas()`. La pantalla, el CSV y el PDF lo llaman igual: los tres muestran lo mismo.
2. Vista PDF (`<modulo>/pdf.blade.php` o `<reporte>-pdf.blade.php`) que extiende `pdf.layout`. dompdf no entiende
   Tailwind ni flex/grid: tablas y CSS simple. El título dice el filtro ("Inventario de Frutas", "Clientes que deben").
3. Responder siempre con `return $this->entregarPdf($pdf, 'Nombre_' . now()->format('Y-m-d') . '.pdf');`
   (`?ver=1` → en línea para imprimir; sin él → descarga). El nombre del archivo lleva el filtro.
4. En la vista: `<x-acciones-exportar :excel="route('x.excel', $filtros)" :pdf="route('x.pdf', $filtros)" />`, con
   `$filtros = array_filter(request()->only([...]))`. Con `pdf` el componente pone los botones PDF e Imprimir.
5. CSV con `Controller::descargarCsv()`. Las ventas anuladas nunca entran (salvo una sección aparte, informativa).

## Filtros
- **Categoría** (`?categoria_id=`): productos, ventas y ganancias, con `<x-selector-categoria :categorias :valor />`.
  Las categorías salen de la tabla, no de una lista fija. En ventas, la venta entra si lleva algo de la categoría y
  aparte se suma solo lo vendido de esa categoría (`totalCategoria`).
- **Clientes** (`?estado=con_deuda|sin_deuda`): con deuda ordenados por saldo y con "debe desde"
  (`Cliente::deudaDesde()`, los abonos cubren primero el fiado más viejo).
- **Fechas**: `date`, y la final `after_or_equal` la inicial, con mensaje en español.
- Validar los filtros (`exists:categorias,id`); un filtro inválido vuelve con error, no muestra todo en silencio.

## Tickets (80 mm)
- Layout `pdf.ticket` (Courier, márgenes 0) y `Controller::entregarTicket($vista, $datos, $lineas, $nombre)`, que fija
  el papel a 80 mm de ancho y calcula el alto por número de líneas. Se abren en el navegador para imprimirlos.
- Existen: `ventas.ticket` y `caja.ticket` (cierre del día, ver skill `caja-diaria`).

## Etiqueta del producto
- `productos.etiqueta`: PDF de 100 × 45 mm con nombre, precio, QR y código de barras (Code 128).
- Los códigos los genera `App\Support\Etiqueta` como SVG en el servidor; la pantalla del kardex muestra los mismos
  SVG y el PDF los pone con `Etiqueta::comoImagen()`. Nada de librerías JS de CDN ni `window.print()`.
- Un código de barras con caracteres que Code 128 no admite no rompe nada: `codigoBarrasSvg()` devuelve null y se avisa.

## Pruebas
- La ruta PDF responde 200 con `Content-Type: application/pdf`; `?ver=1` da `Content-Disposition: inline`.
- El filtro cambia lo que sale: probarlo con el CSV (`->streamedContent()`) o con `viewData()`, que se leen fácil.
- `SqlFecha` solo funciona en MySQL/PostgreSQL: no renderizar Dashboard, Reportes ni Estadísticas en pruebas
  (el CSV y el PDF de ganancias sí se pueden probar).
- Modelo: `tests/Feature/ReportesFiltrosTest.php`.
