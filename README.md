# Los Primos Ramírez — Sistema de gestión

Aplicación Laravel para una distribuidora de frutas y verduras: ventas (contado y fiado), caja,
inventario con kardex, compras, proveedores, mermas, clientes, reportes y estadísticas.

## Ejecutar en local (XAMPP)

```bash
composer install
npm install
# Crear la base "primos_ramirez_db" en MySQL y ajustar .env (DB_*)
php artisan migrate
php artisan db:seed --class=DatosInicialesSeeder   # admin@primosramirez.com / 12345678
php artisan storage:link
npm run build
php artisan serve                                  # http://127.0.0.1:8000
```

Más detalles de los módulos en [MEJORAS.md](MEJORAS.md).

## Moneda y tasa BCV

- Los precios, deudas y costos se llevan en **dólares ($)**; en pantalla se ve también el equivalente en **bolívares (Bs.)**.
- La tasa oficial del BCV se consulta sola (una vez al día, al abrir el sistema con internet) desde `ve.dolarapi.com`, o a mano con `php artisan tasa:actualizar`. Si no hay internet se puede fijar manualmente en **Tasa de cambio**.
- Cada venta guarda la tasa con la que se hizo (su monto en Bs. no cambia después). Se puede cobrar en $ o en Bs.; los abonos de clientes también.
- La caja cuenta el efectivo en $ y en Bs. por separado.

## Reglas de cálculo

- Cantidades y stock admiten decimales (hasta 3) para productos por peso o volumen.
- Las ventas anuladas no cuentan en caja, reportes, estadísticas, kardex ni saldo de clientes.
- Caja: efectivo esperado = fondo inicial + ventas de **contado** del día + **abonos** cobrados.
  Las ventas a crédito no ingresan efectivo hasta que el cliente abona.
- Saldo del cliente = ventas a crédito (no anuladas) − abonos. Si tiene límite de crédito, no se permite superarlo.
- Costo de producto = promedio ponderado de sus compras (`Compra::costosPromedio()`).
"# Primos_Ramirez" 
