# Mejoras agregadas — Los Primos Ramírez App

Este documento resume los módulos nuevos que se agregaron al sistema y cómo dejar
todo funcionando en tu equipo.

## 0. Tema visual: "Verdulería"

El sistema ahora tiene una identidad visual fresca inspirada en una verdulería/
distribuidora de frutas y verduras: fondo cálido color crema, una franja
decorativa verde-naranja-rojo en la parte superior de cada página, tarjetas
blancas limpias, e insignias de estado en colores vivos (verde para "contado"/
activo, naranja para fiado/alertas, rojo para crítico). Se agregaron íconos
lineales de hoja, manzana y zanahoria (sin usar emojis en ningún lugar) como
detalles decorativos en el login y en los estados vacíos de algunas tablas.

## 1. Cómo instalar y correr el proyecto

Requisitos: PHP con Composer, MySQL (XAMPP sirve perfecto), Node.js con npm.

```bash
# 1. Instalar dependencias de PHP
composer install

# 2. Instalar dependencias de JavaScript (Tailwind, Vite, etc.)
npm install

# 3. Configurar tu base de datos en el archivo .env
#    (DB_DATABASE, DB_USERNAME, DB_PASSWORD según tu MySQL/XAMPP)

# 4. Crear las tablas (incluye las nuevas: proveedores, mermas, y los
#    campos nuevos en productos y compras)
php artisan migrate

# 5. (Opcional) Cargar datos de ejemplo: usuario admin, categorías y productos
php artisan db:seed --class=DatosInicialesSeeder
#    Usuario de prueba: admin@primosramirez.com / 12345678

# 6. Compilar los estilos/JS
npm run build
#    (o "npm run dev" mientras desarrollas, para recarga automática)

# 7. Levantar el servidor
php artisan serve
```

Si ya tenías la base de datos con información real, **no uses `migrate:fresh`**
(borra todo). Usa solo `php artisan migrate`, que agrega las tablas y columnas
nuevas sin tocar tus datos existentes.

## 2. Módulos nuevos agregados

### Proveedores
Registro completo de proveedores (nombre, contacto, teléfono, correo, dirección,
notas) con estado activo/inactivo. Se vincula directamente al módulo de Compras:
al registrar una entrada de mercancía puedes elegir un proveedor ya registrado,
o escribir uno nuevo al vuelo si aún no lo has dado de alta.

- Menú: **Gestión → Proveedores**
- No se puede eliminar un proveedor con compras asociadas (se recomienda
  marcarlo como inactivo en su lugar).

### Control de Mermas / Pérdidas
Pensado para el día a día de una distribuidora de frutas y verduras: registra
producto dañado, vencido, robado/extraviado, u otro motivo. Al registrar una
merma se descuenta automáticamente del stock del producto, y no permite
descartar más cantidad de la que hay disponible.

- Menú: **Gestión → Mermas**
- Muestra el valor estimado perdido en el mes (a precio de venta) y el
  historial completo con quién lo registró.

### Unidad de medida por producto
Los productos ya no se manejan solo como "unidades": ahora cada producto tiene
su unidad real (Kg, Lb, Caja, Atado, Saco, Litro, Unidad). Esto se refleja en
el listado de productos, el formulario de ventas y las alertas de stock.

### Stock mínimo y alertas de vencimiento
- Cada producto tiene su propio umbral de "stock crítico" (antes era un valor
  fijo de 5 para todos). Se configura al crear/editar el producto.
- Se agregó **fecha de vencimiento** opcional por producto. El Dashboard
  muestra un aviso cuando un producto está vencido o vence en 5 días o menos.

### Clientes y Cuentas por Cobrar (Fiado)
Nuevo módulo para llevar el control de clientes habituales y sus ventas a
crédito ("fiado"), algo muy común en una distribuidora de barrio:

- Registro de clientes (nombre, teléfono, dirección, límite de crédito opcional).
- Al registrar una venta, ahora puedes elegir **Contado** o **Fiado/Crédito**;
  si eliges fiado, seleccionas el cliente y la venta queda pendiente de cobro.
- Ficha de cada cliente con su **saldo pendiente actual**, historial de ventas
  a crédito y **registro de abonos** (pagos parciales o totales). El sistema no
  deja registrar un abono mayor al saldo pendiente.
- El listado de clientes muestra el total general por cobrar y el saldo de
  cada uno, para saber de un vistazo quién debe y cuánto.

- Menú: **Gestión → Clientes / Fiado**

### Panel de Estadísticas Avanzado
Nueva sección con análisis más profundo que complementa el reporte de
ganancias existente:

- Tendencia de ventas de los últimos 12 meses.
- Comparativo de ingresos por categoría (frutas vs. verduras, etc.).
- Top proveedores por monto total comprado.
- Valor de mermas por mes (para ver el impacto real de las pérdidas).
- **Predicción simple de demanda**: usando el promedio de ventas de las
  últimas 4 semanas, proyecta cuánto se venderá la próxima semana de cada
  producto y avisa si el stock actual alcanza o si conviene reabastecer.

- Menú: **Gestión → Estadísticas**

### Notificaciones dentro del sistema
Campana en la barra superior (visible para Encargado y Dueño) que avisa en
tiempo real sobre:
- Productos en stock crítico.
- Productos vencidos o próximos a vencer.
- Si la caja del día sigue sin abrir o sin cerrar.

## 3. Notas técnicas para quien siga desarrollando

- Los campos nuevos de `productos` son: `unidad_medida`, `stock_minimo`,
  `fecha_vencimiento` (todos con valores por defecto, así que no rompen
  productos ya existentes).
- `compras` ahora tiene `proveedor_id` (FK opcional a `proveedores`); el
  campo de texto `proveedor` se conserva para compatibilidad con registros
  antiguos y como opción de "proveedor no registrado".
- Nueva tabla `mermas` con relación a `productos` y `users`.
- El cálculo de notificaciones vive en `App\Providers\AppServiceProvider`
  (View Composer sobre `partials.nav`), así que aparece en todas las
  páginas sin tener que tocar cada controlador.
