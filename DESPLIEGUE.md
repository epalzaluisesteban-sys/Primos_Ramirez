# Poner Los Primos Ramírez en línea (Supabase + Back4app, planes gratis sin tarjeta)

Esta guía lleva el sistema desde GitHub hasta una dirección pública como `https://primos-ramirez.b4a.run`.
Se hace una sola vez y toma entre 40 y 60 minutos.

| Servicio | Qué hace | Plan |
|---|---|---|
| **GitHub** | Guarda el código. Back4app lo lee de ahí. | Ya está: `epalzaluisesteban-sys/Primos_Ramirez` |
| **Supabase** | Base de datos PostgreSQL, fotos y respaldos. | Gratis, sin tarjeta |
| **Back4app Containers** | Ejecuta el sistema con el `Dockerfile` del repositorio. | Gratis, sin tarjeta: 256 MB de RAM, 0.25 CPU, 100 GB de tráfico al mes |

> **Lee esto antes de empezar.** La página de precios de Back4app no dice cuánto tiempo queda encendido un
> contenedor gratis, pero la guía de ejemplo de Back4app en GitHub (`back4app-templates/container-deploy-lab`) dice
> que desde el 15 de septiembre de 2026 la dirección del plan gratis **dura 60 minutos por despliegue**. Si es así,
> el plan gratis sirve para probar, no para trabajar todo el día. El paso 7 es la prueba para saberlo antes de cargar
> datos reales.

> **Qué se probó y qué no.** Todas las migraciones y un día completo de trabajo (caja, compras, ventas en $ y Bs.,
> fiado, abonos, mermas, anulación, cierre, todas las pantallas, PDF, Excel, crear y restaurar un respaldo) se
> probaron contra PostgreSQL en la PC. No se probó contra tus cuentas de Supabase y Back4app, ni la construcción de
> la imagen Docker: para eso está la lista de verificación del paso 8.

---

## 0. Antes de empezar

Necesitas:

- La cuenta de GitHub donde está el repositorio (`epalzaluisesteban-sys`).
- Un correo para crear las cuentas de Supabase y Back4app (puedes entrar a las dos con GitHub).
- Esta PC con el proyecto, para generar la clave de la aplicación (paso 3).

Mientras avanzas vas a copiar varios valores. Anótalos en un gestor de contraseñas o en un bloc de notas **en tu
PC** (nunca en el repositorio, que es público). Esta es la hoja que vas a llenar:

```text
--- Supabase: base de datos (paso 1) ---
Contraseña de la base de datos:  ________________
DB_HOST:                         ________________   (ej. aws-0-us-west-2.pooler.supabase.com)
DB_USERNAME:                     ________________   (ej. postgres.abcdefghijklmnop)

--- Supabase: almacenamiento (paso 2) ---
AWS_ENDPOINT:                    https://________________.supabase.co/storage/v1/s3
AWS_DEFAULT_REGION:              ________________   (ej. us-west-2)
AWS_ACCESS_KEY_ID:               ________________
AWS_SECRET_ACCESS_KEY:           ________________
AWS_URL:                         https://________________.supabase.co/storage/v1/object/public/fotos

--- Aplicación (paso 3) ---
APP_KEY:                         base64:________________
ADMIN_EMAIL:                     ________________
ADMIN_PASSWORD:                  ________________
```

---

## 1. Supabase: crear el proyecto y la base de datos

1. Entra a <https://supabase.com> → **Start your project** → inicia sesión (con GitHub es lo más rápido).
2. Si te pide crear una organización, ponle cualquier nombre (ej. `Primos Ramirez`), tipo **Personal**, plan **Free**.
3. **New project** y llena:
   - **Name**: `primos-ramirez`.
   - **Database Password**: pulsa **Generate a password** y **cópiala en tu hoja**.
     Supabase no la vuelve a mostrar. Una contraseña generada solo trae letras y números, y eso evita problemas.
   - **Region**: una de Estados Unidos (East US o West US). Back4app solo tiene servidores en ese país, y cada
     consulta a la base de datos viaja menos.
4. **Create new project**. Tarda uno o dos minutos en quedar listo.
5. Cuando termine, pulsa **Connect** (arriba, en la barra del proyecto) y busca el recuadro **Shared pooler**
   (en otras versiones se llama *Session pooler*). Es el que usa el puerto **5432**. No uses *Direct connection* ni
   *Transaction pooler*.

   Debajo, en **Connection parameters**, están los valores separados:

   | Parámetro | Va en | Ejemplo |
   |---|---|---|
   | `host` | `DB_HOST` | `aws-0-us-west-2.pooler.supabase.com` |
   | `user` | `DB_USERNAME` | `postgres.abcdefghijklmnop` (**con** el punto y lo que sigue) |
   | `port` | `DB_PORT` | `5432` |
   | `database` | `DB_DATABASE` | `postgres` |

   > Por qué *Shared pooler*: la conexión directa de Supabase es solo IPv6 y muchos servidores no la alcanzan. El
   > *Transaction pooler* (puerto 6543) no admite las consultas preparadas que usa Laravel.

No hace falta crear tablas: el sistema las crea solo la primera vez que arranca.

**Si pierdes la contraseña de la base de datos:** no se recupera, se cambia. En **Connect** (o en
**Project Settings → Database**) pulsa **Reset database password**, copia la nueva y actualiza `DB_PASSWORD` en
Back4app (paso 9).

---

## 2. Supabase: almacenamiento para fotos y respaldos

En el menú izquierdo del proyecto, entra a **Storage**.

### 2.1 Bucket de fotos (público)

1. **New bucket**.
2. **Name**: `fotos` (en minúsculas, exactamente así).
3. Activa **Public bucket**. Las fotos se muestran en el sistema por su enlace.
4. **Create bucket**.

### 2.2 Bucket de respaldos (privado)

1. **New bucket** otra vez.
2. **Name**: `respaldos`.
3. **No** actives *Public bucket*. Los respaldos traen datos de clientes y contraseñas cifradas.
4. **Create bucket**.

### 2.3 Claves para que el sistema guarde archivos

1. En **Storage**, abre la configuración de **S3** (aparece como *S3 Configuration* o *S3 Connection*).
2. Copia en tu hoja:
   - **Endpoint** → `AWS_ENDPOINT` (termina en `/storage/v1/s3`).
   - **Region** → `AWS_DEFAULT_REGION` (ej. `us-west-2`).
3. En **Access keys** pulsa **New access key**, ponle una descripción (`back4app`) y crea la clave. Copia:
   - **Access key ID** → `AWS_ACCESS_KEY_ID`.
   - **Secret access key** → `AWS_SECRET_ACCESS_KEY`. **Solo se muestra esta vez.** Si la pierdes, borra esa
     clave y crea otra.
4. Arma `AWS_URL` con la misma dirección del endpoint, cambiando el final:

   ```text
   AWS_ENDPOINT = https://abcdefghijklmnop.supabase.co/storage/v1/s3
   AWS_URL      = https://abcdefghijklmnop.supabase.co/storage/v1/object/public/fotos
   ```

> Estas claves dan acceso total al almacenamiento. Solo van en el panel de Back4app, nunca en el código ni en un chat.

---

## 3. Preparar los valores de la aplicación

### 3.1 Clave de la aplicación (`APP_KEY`)

En esta PC, en la carpeta del proyecto, abre una terminal y ejecuta:

```bash
php artisan key:generate --show
```

Muestra algo como `base64:Xy3k...=`. Cópialo **completo, con `base64:`**, en `APP_KEY` de tu hoja.
Este comando no cambia nada de tu `.env`: solo muestra una clave nueva.

### 3.2 Tu usuario Dueño

- `ADMIN_EMAIL`: el correo con el que vas a entrar (ej. el tuyo).
- `ADMIN_PASSWORD`: una contraseña **nueva y fuerte** (12 caracteres o más). No uses `12345678`, que aparece en
  el código público.

Este usuario se crea solo la primera vez, y solo si no existe ningún Dueño. El resto del personal lo creas después
desde **Personal**.

---

## 4. Back4app: crear la aplicación

1. Entra a <https://www.back4app.com> → **Sign up** (puedes entrar con GitHub). No pide tarjeta.
   Si pregunta qué quieres crear, elige **Containers** (*Container as a Service*), no *Backend as a Service*.
2. **Build new app** (o **New app**) → **Containers as a Service**.
3. **Connect GitHub** y dale acceso al repositorio `Primos_Ramirez`. Luego elígelo con **Select**.
4. Llena la configuración (los nombres pueden variar un poco):

   | Campo | Valor |
   |---|---|
   | **App Name** | `primos-ramirez` |
   | **Branch** | `main` |
   | **Root Directory** | `./` (déjalo como está) |
   | **Port** | `80`, o vacío (lo toma del `Dockerfile`) |
   | **Health Check** (si aparece) | `/up` |

5. Si el formulario trae **Environment Variables**, agrega allí las del paso 5. Si no, crea la app y agrégalas
   después en la app → **Settings** → **Environment**.
6. **Create App**. El primer despliegue va a fallar si todavía no pusiste las variables: es normal.

---

## 5. Variables de entorno

Van **todas**, una por una (nombre a la izquierda, valor a la derecha, sin comillas y sin espacios al inicio ni al
final). Las que dicen *tu hoja* salen de los pasos 1 a 3:

```text
APP_NAME                     Los Primos Ramírez
APP_ENV                      production
APP_DEBUG                    false
APP_URL                      https://primos-ramirez.b4a.run        (ver nota abajo)
APP_KEY                      (tu hoja, con base64:)
APP_LOCALE                   es
APP_FALLBACK_LOCALE          es
LOG_CHANNEL                  stderr
LOG_LEVEL                    warning

DB_CONNECTION                pgsql
DB_HOST                      (tu hoja)
DB_PORT                      5432
DB_DATABASE                  postgres
DB_USERNAME                  (tu hoja, con el punto y lo que sigue)
DB_PASSWORD                  (tu hoja)
DB_SSLMODE                   require

SESSION_DRIVER               database
SESSION_SECURE_COOKIE        true
CACHE_STORE                  database
QUEUE_CONNECTION             database

MEDIA_DISK                   s3
AWS_ACCESS_KEY_ID            (tu hoja)
AWS_SECRET_ACCESS_KEY        (tu hoja)
AWS_DEFAULT_REGION           (tu hoja, ej. us-west-2)
AWS_BUCKET                   fotos
AWS_ENDPOINT                 (tu hoja)
AWS_URL                      (tu hoja, termina en /object/public/fotos)
AWS_USE_PATH_STYLE_ENDPOINT  true
RESPALDOS_DISK               respaldos
AWS_BUCKET_RESPALDOS         respaldos

ADMIN_NAME                   Administrador
ADMIN_EMAIL                  (tu hoja)
ADMIN_PASSWORD               (tu hoja)

APACHE_MAX_WORKERS           3
PHP_MEMORY_LIMIT             128M
```

- **`APP_URL`**: la dirección real la ves arriba en la app de Back4app cuando termina el despliegue (termina en
  `.b4a.run`). Si es distinta a la que pusiste, corrígela y vuelve a desplegar.
- **`APACHE_MAX_WORKERS` y `PHP_MEMORY_LIMIT`** existen por los 256 MB del plan gratis: limitan cuántas páginas
  se atienden a la vez (3) y cuánta memoria puede usar cada una. Sin ellas, varias pantallas abiertas al mismo tiempo
  pueden agotar la memoria y Back4app reinicia el contenedor. En un plan con más memoria se pueden subir.
- **Cada cambio de variable se aplica en el siguiente despliegue.** Después de guardar, pulsa **Deploy** (o
  *Deploy the latest commit*).

---

## 6. Primer despliegue

1. Con las variables puestas, pulsa **Deploy**. La primera construcción tarda entre 5 y 15 minutos.
2. Cuando arranca, el contenedor (lo puedes ver en **Logs**):
   - crea las tablas en Supabase (verás líneas `Running migrations` y una por cada tabla con `DONE`);
   - crea tu usuario Dueño;
   - consulta la tasa del BCV;
   - inicia el servidor web.
3. Cuando el estado diga **Ready** (o *Available*), abre la dirección `https://....b4a.run`.
4. Entra con `ADMIN_EMAIL` y `ADMIN_PASSWORD`.

---

## 7. Prueba de duración (antes de cargar datos reales)

1. Anota la hora a la que terminó el despliegue.
2. Vuelve a abrir la dirección **70 minutos después**.
   - **Si abre**, el plan gratis se mantiene encendido: sigue con el paso 8.
   - **Si no abre** (error, página de Back4app o "app stopped"), el plan gratis se apaga a la hora. Para trabajar
     todo el día hace falta el plan pago de Back4app u otro alojamiento que ejecute el mismo `Dockerfile`.
     Tus datos no se pierden: están en Supabase.

---

## 8. Lista de verificación (en este orden)

- [ ] Se abre el login y entras con tu usuario Dueño.
- [ ] Arriba aparece la tasa BCV. Si no, ve a **Tasa del dólar (BCV)** → **Actualizar desde el BCV**.
- [ ] Crea una categoría y un producto **con foto**: la foto se ve. En Supabase → Storage → `fotos` aparece el archivo.
- [ ] Abre la caja, registra una compra, una venta en $ y otra en Bs.
- [ ] Abre **Inicio**, **Estadísticas** y **Ganancias** (prueban las consultas en PostgreSQL).
- [ ] Descarga un PDF y un Excel.
- [ ] **Administración → Respaldo y restauración → Crear y descargar respaldo**: se descarga el archivo y en
      Supabase → Storage → `respaldos` aparece dentro de la carpeta `respaldos`.
- [ ] Crea los usuarios del personal en **Personal** y prueba entrar con uno de ellos.

Si todo está marcado, el sistema está en línea. Los datos de prueba que creaste los puedes desactivar o anular
desde el mismo sistema.

---

## 9. Cuando algo falla

El motivo casi siempre está en la app de Back4app → **Logs**. Busca la primera línea con `ERROR`, `SQLSTATE` o
`Exception`.

| Lo que ves | Causa probable | Qué hacer |
|---|---|---|
| `Tenant or user not found` | `DB_USERNAME` sin la parte `.abcdefghijklmnop` | Copia el usuario completo del *Shared pooler* |
| `password authentication failed` | `DB_PASSWORD` mal copiada | Pégala de nuevo, o en Supabase → **Reset database password** y actualízala en Back4app |
| `could not translate host name` / `Network is unreachable` | Se usó la conexión directa (`db.xxxx.supabase.co`) | Usa el host del **Shared pooler** (`aws-0-...pooler.supabase.com`) |
| `prepared statement ... does not exist` | Puerto 6543 (*Transaction pooler*) | `DB_PORT` debe ser `5432` |
| `No application encryption key has been specified` / `Unsupported cipher` | `APP_KEY` vacía o sin `base64:` | Pega la clave completa del paso 3.1 |
| *Health check failed* | El sistema no arrancó; casi siempre es una variable de la base de datos | Busca en **Logs** la primera línea con `ERROR` o `SQLSTATE` |
| El contenedor se reinicia solo, o en **Logs** aparece `Killed` / *out of memory* | Se acabaron los 256 MB | Revisa que estén `APACHE_MAX_WORKERS=3` y `PHP_MEMORY_LIMIT=128M` |
| `Allowed memory size of 134217728 bytes exhausted` al restaurar un respaldo | El respaldo es demasiado grande para 128 MB | Restaura ese respaldo en la PC, o en un plan con más memoria |
| Página **419 Page Expired** al entrar | La página de login quedó abierta mucho tiempo | Recarga el login y vuelve a entrar; si se repite, busca en **Logs** un error de base de datos |
| La foto se sube pero no se ve | Bucket `fotos` sin *Public* o `AWS_URL` mal escrita | Marca el bucket como público; revisa que `AWS_URL` termine en `/object/public/fotos` |
| Error al subir una foto o al crear un respaldo (`SignatureDoesNotMatch`, `InvalidAccessKeyId`) | Claves S3 o región mal copiadas | Revisa `AWS_*`; si dudas, crea una clave nueva en Supabase |
| Cambiaste una variable y no pasó nada | Las variables se aplican en el siguiente despliegue | Pulsa **Deploy** |
| El despliegue falla antes de llegar a `Running migrations` | Error al construir la imagen | Copia las últimas 30 líneas del log de construcción y pide ayuda con ellas |

Para cambiar una variable: app → **Settings** → **Environment** → edita → guarda → **Deploy**.

---

## 10. Después: el día a día

- **Actualizar el sistema.** Sube los cambios a GitHub y luego pulsa **Deploy** en Back4app (en el plan gratis no
  despliega solo):

  ```bash
  git add .
  git commit -m "qué cambió"
  git push
  ```

  Las migraciones nuevas se aplican solas al arrancar. Los datos no se pierden: viven en Supabase.
- **Supabase gratis pausa el proyecto tras 7 días sin actividad.** Para evitarlo, crea un monitor gratis en
  <https://uptimerobot.com> (**Add New Monitor** → tipo *HTTP(s)*) que visite `https://TU-DIRECCION.b4a.run/login`
  cada 10 minutos. Usa `/login` y no `/up`: el login consulta la base de datos. Si se pausa, entra a Supabase y pulsa
  **Restore project**.
- **Supabase gratis no hace copias de seguridad automáticas.** Crea un respaldo desde el sistema con regularidad
  (el Inicio le avisa al Dueño si pasan más de 7 días) y guarda el archivo descargado fuera del servidor: Drive, correo o
  memoria USB.
- **El disco del contenedor se borra en cada despliegue o reinicio.** Por eso las fotos y los respaldos van a
  Supabase. Los archivos que subas para restaurar solo duran hasta que confirmes la restauración.

---

## 11. Seguridad

- El repositorio es **público**. No subas el `.env` ni ninguna clave; las claves van solo en el panel de Back4app.
- En producción no se ejecuta `DatosInicialesSeeder` (el que crea el usuario de prueba `admin@primosramirez.com`
  con contraseña `12345678`). El Dueño se crea con `ADMIN_EMAIL` / `ADMIN_PASSWORD`.
- `APP_DEBUG` debe seguir en `false`: con `true` los errores muestran datos internos a cualquiera.
- Si alguna clave se filtra (la pegaste en un chat, en un archivo del repositorio, etc.), cámbiala: en Supabase
  crea una clave S3 nueva o cambia la contraseña de la base de datos, y actualiza la variable en Back4app.
