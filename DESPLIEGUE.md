# Poner Los Primos Ramírez en línea (Supabase + Render, planes gratis)

- **Supabase**: base de datos PostgreSQL y almacenamiento de fotos y respaldos.
- **Render**: ejecuta la aplicación con el `Dockerfile` y el `render.yaml` de este repositorio.
- **GitHub**: guarda el código; Render lo lee de ahí.

> Qué se probó: todas las migraciones y un recorrido completo (caja, compras, ventas en $ y Bs., fiado, abonos,
> mermas, anulación, cierre, todas las pantallas, PDF, Excel, crear y restaurar un respaldo) contra PostgreSQL
> en la PC. No se probó contra tus cuentas de Supabase y Render: para eso es la lista de verificación del final.

## 1. Supabase

1. Crea un proyecto en <https://supabase.com>. En **Region** elige **East US (North Virginia)**, la misma región
   que usa Render en `render.yaml`. Guarda la **contraseña de la base de datos**.
2. Botón **Connect** → pestaña **Session pooler**. Anota:
   - Host: `aws-0-us-east-1.pooler.supabase.com` (o el que te muestre) · Puerto: `5432`
   - Usuario: `postgres.TU_REF` (lleva el punto) · Base: `postgres`
   > Usa *Session pooler* (puerto 5432). La conexión directa de Supabase es solo IPv6 y Render no la alcanza;
   > el modo *Transaction* (puerto 6543) rompe Laravel.
3. **Storage → New bucket**, dos veces:
   - `fotos`, marcado como **Public bucket** (las fotos se ven en el sistema por su enlace).
   - `respaldos`, **sin** marcar *Public* (trae datos de clientes y contraseñas cifradas).
4. **Storage → S3 Connection**: copia el *Endpoint* (`https://TU_REF.supabase.co/storage/v1/s3`) y la *Region*;
   pulsa **New access key** y guarda el *Access key ID* y el *Secret access key* (el secreto solo se ve una vez).

## 2. GitHub

El repositorio es `https://github.com/epalzaluisesteban-sys/Primos_Ramirez` y es **público**: cualquiera puede
leer el código. Por eso las claves nunca van en el repositorio, solo en el panel de Render. El `.env` ya está
excluido por `.gitignore`.

```bash
git remote set-url origin https://github.com/epalzaluisesteban-sys/Primos_Ramirez.git
git push -u origin main
```

Después de cada cambio: `git add .`, `git commit -m "qué cambió"` y `git push`. Render vuelve a desplegar solo.

## 3. Render

1. En <https://render.com>: **New → Blueprint** → conecta el repositorio → Render lee `render.yaml`.
2. Completa las variables que pide (las que en `render.yaml` dicen `sync: false`):

   | Variable | Valor |
   |---|---|
   | `APP_KEY` | resultado de `php artisan key:generate --show` (ejecútalo en tu PC; copia todo, con `base64:`) |
   | `APP_URL` | `https://NOMBRE.onrender.com` (la URL que te asigne Render; si aún no la sabes, ponla después y vuelve a desplegar) |
   | `DB_HOST` / `DB_USERNAME` / `DB_PASSWORD` | del *Session pooler* (paso 1.2) |
   | `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY` | paso 1.4 |
   | `AWS_DEFAULT_REGION` | la *Region* del paso 1.4 (ej. `us-east-1`) |
   | `AWS_ENDPOINT` | `https://TU_REF.supabase.co/storage/v1/s3` |
   | `AWS_URL` | `https://TU_REF.supabase.co/storage/v1/object/public/fotos` |
   | `ADMIN_EMAIL` / `ADMIN_PASSWORD` | tu usuario Dueño y una **contraseña nueva y fuerte** |

3. **Deploy**. La primera vez tarda varios minutos (construye la imagen). Al arrancar, el contenedor crea las
   tablas, crea tu usuario Dueño (solo si no existe ninguno) y consulta la tasa del BCV.
4. Entra a la URL de Render con `ADMIN_EMAIL` / `ADMIN_PASSWORD`. Después crea al resto del personal en **Personal**.

Si el despliegue falla, el motivo está en Render → tu servicio → **Logs**. Lo más común: una variable mal copiada
(espacios de más, `DB_USERNAME` sin el `.TU_REF`) o el puerto 6543 en vez de 5432.

## 4. Lista de verificación (en este orden)

- [ ] Se abre el login y entras.
- [ ] Arriba aparece la tasa BCV (si no, **Tasa del dólar (BCV) → Actualizar desde el BCV**).
- [ ] Crea una categoría y un producto **con foto** → la foto se ve (prueba el bucket `fotos`).
- [ ] Abre la caja, registra una compra, una venta en $ y otra en Bs.
- [ ] Abre Inicio, Estadísticas y Ganancias (prueban las consultas en PostgreSQL).
- [ ] Descarga un PDF y un Excel.
- [ ] **Administración → Respaldo y restauración → Crear y descargar respaldo**: se descarga el archivo y en Supabase
      aparece dentro del bucket `respaldos`.

## 5. Limitaciones de los planes gratis

- **Render gratis se duerme** tras 15 min sin visitas; la primera carga tarda cerca de un minuto. Para evitarlo,
  crea un monitor gratis en <https://uptimerobot.com> que visite `https://TU-URL/login` cada 10 min
  (`/login` y no `/up`: el login usa la base de datos, y eso es lo que mantiene despierto a Supabase).
- **Supabase gratis pausa el proyecto tras 7 días sin actividad** (ese mismo monitor lo mantiene activo) y **no
  hace copias de seguridad automáticas**. Crea un respaldo desde el sistema con regularidad (el Inicio avisa al
  Dueño si pasan 7 días) y guarda el archivo descargado fuera del servidor: Drive, correo o memoria USB.
- El disco de Render se borra en cada reinicio. Por eso las fotos y los respaldos van a Supabase; los archivos
  que subas para restaurar solo duran hasta que confirmes la restauración.

## 6. Seguridad

- No subas el `.env` ni claves a GitHub; las claves van solo en el panel de Render.
- En producción no se ejecuta `DatosInicialesSeeder` (el que crea el usuario de prueba `admin@primosramirez.com`):
  el Dueño se crea con `ADMIN_EMAIL` / `ADMIN_PASSWORD`. Esa contraseña de prueba está en el código público.
- `APP_DEBUG` debe seguir en `false`: con `true` los errores muestran datos internos a cualquiera.
