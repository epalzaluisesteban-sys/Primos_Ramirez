# Poner Los Primos Ramírez en línea (Supabase + Render, planes gratis)

- **Supabase**: base de datos PostgreSQL y almacenamiento de fotos.
- **Render**: ejecuta la aplicación (usa el `Dockerfile` y el `render.yaml` de este repositorio).
- **GitHub**: guarda el código; Render lo lee de ahí.

> Nada de esto se probó contra tus cuentas reales (no tengo acceso). Por eso al final hay una lista de verificación.

## 1. Supabase

1. Crea un proyecto en <https://supabase.com> (elige la región más cercana, p. ej. *South America – São Paulo*) y guarda la **contraseña de la base de datos**.
2. Botón **Connect** → pestaña **Session pooler**. Anota:
   - Host: `aws-0-XXXX.pooler.supabase.com` · Puerto: `5432`
   - Usuario: `postgres.TU_REF` (lleva el punto) · Base: `postgres`
   > Usa *Session pooler* (puerto 5432). La conexión directa de Supabase es solo IPv6 y Render no la alcanza; el modo *Transaction* (6543) rompe Laravel.
3. **Storage → New bucket**: nombre `fotos`, marcado como **Public bucket**.
4. **Storage → S3 Connection**: copia el *Endpoint* (`https://TU_REF.supabase.co/storage/v1/s3`) y la *Region*; pulsa **New access key** y guarda el *Access key ID* y el *Secret*.

## 2. GitHub (cuenta distinta)

En la carpeta del proyecto (el `.env` y `vendor` ya están excluidos por `.gitignore`):

```bash
git init
git add .
git commit -m "Los Primos Ramírez"
git branch -M main
git remote add origin https://github.com/TU_USUARIO/TU_REPO.git
git push -u origin main
```

Usa un repositorio **privado**. Si Git usa otra cuenta guardada, inicia sesión con la correcta (o usa un *Personal Access Token*).

## 3. Render

1. En <https://render.com>: **New → Blueprint** → conecta el repositorio → detecta `render.yaml`.
2. Completa las variables que piden (las que dicen `sync: false`):

   | Variable | Valor |
   |---|---|
   | `APP_KEY` | resultado de `php artisan key:generate --show` (ejecútalo en tu PC) |
   | `APP_URL` | `https://NOMBRE.onrender.com` (la URL que te asigne Render) |
   | `DB_HOST` / `DB_USERNAME` / `DB_PASSWORD` | del *Session pooler* (paso 1.2) |
   | `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY` | paso 1.4 |
   | `AWS_DEFAULT_REGION` | región del paso 1.4 |
   | `AWS_ENDPOINT` | `https://TU_REF.supabase.co/storage/v1/s3` |
   | `AWS_URL` | `https://TU_REF.supabase.co/storage/v1/object/public/fotos` |
   | `ADMIN_EMAIL` / `ADMIN_PASSWORD` | tu usuario Dueño y una **contraseña nueva y fuerte** |

3. **Deploy**. Al arrancar, el contenedor crea las tablas, crea tu usuario Dueño (solo si no existe ninguno) y consulta la tasa del BCV.
4. Entra a la URL de Render con `ADMIN_EMAIL` / `ADMIN_PASSWORD`.

## 4. Lista de verificación (en este orden)

- [ ] Se abre el login y entras.
- [ ] Arriba aparece la tasa BCV (si no, **Tasa de cambio → Actualizar desde el BCV**).
- [ ] Crea una categoría y un producto **con foto** → la foto se ve (prueba Supabase Storage).
- [ ] Registra una compra y una venta en $ y otra en Bs.
- [ ] Abre Dashboard, Estadísticas y Reporte de ganancias (prueban las consultas en PostgreSQL).
- [ ] Descarga un PDF y un Excel.

## 5. Limitaciones de los planes gratis

- **Render gratis se duerme** tras 15 min sin visitas; la primera carga tarda ~1 min. Para evitarlo, crea un monitor gratis en <https://uptimerobot.com> que visite `https://TU-URL/login` cada 5–10 min.
- **Supabase gratis pausa el proyecto tras 7 días sin actividad** (ese mismo monitor lo mantiene activo) y **no incluye copias de seguridad automáticas**. Descarga una copia con regularidad:
  ```bash
  pg_dump "postgresql://postgres.TU_REF:CLAVE@HOST:5432/postgres" -F c -f respaldo.dump
  ```
- Los logs de Render son temporales; la información real (ventas, caja, fotos) vive en Supabase.

## 6. Seguridad

- No subas el `.env` ni claves a GitHub; las claves van solo en el panel de Render.
- Cambia la contraseña de ejemplo `12345678` si alguna vez creaste ese usuario en la base de producción (en producción no se crea: usa `ADMIN_PASSWORD`).
- `APP_DEBUG` debe seguir en `false`.
