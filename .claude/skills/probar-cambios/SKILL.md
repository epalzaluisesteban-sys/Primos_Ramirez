---
name: probar-cambios
description: Cómo verificar cambios en Los Primos Ramírez - pruebas automáticas (PHPUnit con SQLite), revisar vistas compiladas y ver la app en el navegador con el preview "laravel". Úsala antes de dar un cambio por terminado.
---

# Probar cambios

## 1. Revisión rápida
```bash
php -l ruta/al/archivo.php          # sintaxis de cada PHP tocado
php artisan view:cache && php artisan view:clear   # compila todas las vistas Blade
php artisan route:list --except-vendor             # si tocaste rutas
```

## 2. Pruebas automáticas
```bash
php artisan test
php artisan test --filter=IntegridadHistorialTest
```
- Usan SQLite en memoria (`phpunit.xml`) con `RefreshDatabase`; corren todas las migraciones.
- Crea usuarios con `User::factory()->create(['rol' => 'Dueno'])` y actúa con `actingAs()`.
- En `setUp()` llama `CierreCaja::olvidarEstados()` (caché estática por request).
- **No renderices** Dashboard, Reportes ni Estadísticas en pruebas: `SqlFecha` no soporta SQLite.
  Para el resto, comprueba la redirección y `assertSessionHasErrors('error')` / `assertSessionHasNoErrors()`
  sin seguir el redirect. Las exportaciones CSV se prueban con `->streamedContent()`.
- Si una prueba renderiza el layout, la campana llama a la API del BCV: usa `Http::fake()`.

## 3. En el navegador
- Inicia el preview `laravel` (`.claude/launch.json`, `php artisan serve` en el puerto 8000). Necesita MySQL de XAMPP
  encendido y `php artisan migrate` aplicado.
- Entra con un usuario de prueba de la base local (el seeder `DatosInicialesSeeder` crea uno; ver README).
- Revisa: consola sin errores, la pantalla en claro y oscuro, y el ancho de móvil (375 px).
- Si no hay `public/build`, corre `npm run build` (o `npm run dev`).

## 4. Antes de terminar
- Los cambios de reglas de negocio llevan prueba en `tests/Feature`.
- Actualiza `CLAUDE.md` si cambió una regla o la estructura.
