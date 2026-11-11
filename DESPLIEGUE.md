# Poner Los Primos Ramírez en línea (Supabase + Render, planes gratis)

Esta guía lleva el sistema desde GitHub hasta una dirección pública como `https://primos-ramirez.onrender.com`.
Se hace una sola vez y toma entre 40 y 60 minutos.

| Servicio | Qué hace | Plan |
|---|---|---|
| **GitHub** | Guarda el código. Render lo lee de ahí. | Ya está: `epalzaluisesteban-sys/Primos_Ramirez` |
| **Supabase** | Base de datos PostgreSQL, fotos y respaldos. | Gratis |
| **Render** | Ejecuta el sistema (usa el `Dockerfile` y el `render.yaml` del repositorio). | Gratis (puede pedir una tarjeta para verificar la identidad; el plan Free no cobra) |

> **Qué se probó y qué no.** Todas las migraciones y un día completo de trabajo (caja, compras, ventas en $ y Bs.,
> fiado, abonos, mermas, anulación, cierre, todas las pantallas, PDF, Excel, crear y restaurar un respaldo) se
> probaron contra PostgreSQL en la PC. No se probó contra tus cuentas de Supabase y Render, ni la construcción de la
> imagen Docker: para eso está la lista de verificación del paso 6.

---

## 0. Antes de empezar

Necesitas:

- La cuenta de GitHub donde está el repositorio (`epalzaluisesteban-sys`).
- Un correo para crear las cuentas de Supabase y Render (puedes entrar a las dos con GitHub).
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
   - **Region**: **West US (Oregon)**. Render está en la misma región (`render.yaml` dice `region: oregon`) y cada
     consulta a la base de datos viaja menos. Si eliges otra, cambia `region` en `render.yaml` por la de Render más
     cercana (`virginia` para East US).
4. **Create new project**. Tarda uno o dos minutos en quedar listo.
5. Cuando termine, pulsa **Connect** (arriba, en la barra del proyecto) y busca el recuadro **Shared pooler**
   (en otras versiones se llama *Session pooler*). Es el que usa el puerto **5432**. No uses *Direct connection* ni
   *Transaction pooler*.

   Debajo, en **Connection parameters**, están los valores separados:

   | Parámetro | Va en | Ejemplo |
   |---|---|---|
   | `host` | `DB_HOST` | `aws-0-us-west-2.pooler.supabase.com` |
   | `user` | `DB_USERNAME` | `postgres.abcdefghijklmnop` (**con** el punto y lo que sigue) |
   | `port` | ya está en `render.yaml` | `5432` |
   | `database` | ya está en `render.yaml` | `postgres` |

   > Por qué *Shared pooler*: la conexión directa de Supabase es solo IPv6 y Render no la alcanza. El
   > *Transaction pooler* (puerto 6543) no admite las consultas preparadas que usa Laravel.

No hace falta crear tablas: el sistema las crea solo la primera vez que arranca en Render.

**Si pierdes la contraseña de la base de datos:** no se recupera, se cambia. En **Connect** (o en
**Project Settings → Database**) pulsa **Reset database password**, copia la nueva y actualiza `DB_PASSWORD` en
Render (paso 7).

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
3. En **Access keys** pulsa **New access key**, ponle una descripción (`render`) y crea la clave. Copia:
   - **Access key ID** → `AWS_ACCESS_KEY_ID`.
   - **Secret access key** → `AWS_SECRET_ACCESS_KEY`. **Solo se muestra esta vez.** Si la pierdes, borra esa
     clave y crea otra.
4. Arma `AWS_URL` con la misma dirección del endpoint, cambiando el final:

   ```text
   AWS_ENDPOINT = https://abcdefghijklmnop.supabase.co/storage/v1/s3
   AWS_URL      = https://abcdefghijklmnop.supabase.co/storage/v1/object/public/fotos
   ```

> Estas claves dan acceso total al almacenamiento. Solo van en el panel de Render, nunca en el código ni en un chat.

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

## 4. Render: crear el servicio

1. Entra a <https://render.com> → **Get Started** → inicia sesión **con GitHub** (con la cuenta
   `epalzaluisesteban-sys`). Así Render puede leer el repositorio.
2. En el panel: **New** → **Blueprint**.
3. **Connect a repository**: si no aparece `Primos_Ramirez`, pulsa **Configure GitHub** (o *Connect GitHub*) y
   dale acceso a ese repositorio. Luego elígelo.
4. **Blueprint Name**: `primos-ramirez`. **Branch**: `main`. Render lee el `render.yaml` y muestra que va a crear
   un servicio web llamado `primos-ramirez` (Docker, plan *Free*, región Oregon).
5. Render pide las variables secretas (las que en `render.yaml` dicen `sync: false`). Pégalas desde tu hoja,
   **sin espacios al inicio ni al final**:

   | Variable | Qué pegar |
   |---|---|
   | `APP_KEY` | La clave del paso 3.1, con `base64:` |
   | `APP_URL` | `https://primos-ramirez.onrender.com` (ver nota abajo) |
   | `DB_HOST` | Del paso 1.5 |
   | `DB_USERNAME` | Del paso 1.5, con el `.` y lo que sigue |
   | `DB_PASSWORD` | La contraseña de la base de datos del paso 1.3 |
   | `AWS_ACCESS_KEY_ID` | Paso 2.3 |
   | `AWS_SECRET_ACCESS_KEY` | Paso 2.3 |
   | `AWS_DEFAULT_REGION` | Paso 2.3 (ej. `us-west-2`) |
   | `AWS_ENDPOINT` | Paso 2.3 |
   | `AWS_URL` | Paso 2.3 (termina en `/public/fotos`) |
   | `ADMIN_EMAIL` | Paso 3.2 |
   | `ADMIN_PASSWORD` | Paso 3.2 |

   > **Nota sobre `APP_URL`:** Render arma la dirección con el nombre del servicio: `https://primos-ramirez.onrender.com`.
   > Si ese nombre ya lo tiene otra persona, Render agrega letras al final (ej. `primos-ramirez-x7k2.onrender.com`).
   > Pon la de arriba; cuando el servicio exista, revisa la dirección real (paso 5.4) y corrígela si es distinta.

   Las demás variables (base de datos `postgres`, puerto `5432`, buckets `fotos` y `respaldos`, idioma, etc.) ya
   vienen escritas en `render.yaml`.
6. **Apply** (o *Deploy Blueprint*).

> Render solo pide las variables `sync: false` al crear el Blueprint. Si después necesitas cambiar una, se hace en
> el servicio → **Environment** (paso 7).

### Si Render pide una tarjeta

Render puede pedir una tarjeta (**Add Card**) para verificar la identidad antes de crear el servicio. El plan Free no
cobra. El país y la dirección deben ser los de la tarjeta, tal como los tiene el banco o la app que la emite; si no
coinciden, la verificación falla. Escribe esos datos tú mismo en Render, nunca en un chat.

### Si no te aparece la opción Blueprint

Crea el servicio a mano: **New** → **Web Service** → elige el repositorio → **Language/Runtime: Docker** →
**Region: Oregon (US West)** → **Instance Type: Free**. En **Environment Variables** agrega **todas** las
variables de `render.yaml` (las que tienen `value:` con ese mismo valor, y las de la tabla de arriba).
**Health Check Path**: `/up`.

---

## 5. Primer despliegue

1. Render empieza a construir la imagen. La primera vez tarda entre 5 y 15 minutos.
   Puedes verlo en el servicio `primos-ramirez` → **Logs** (o **Events** → el despliegue en curso).
2. Cuando arranca, el contenedor:
   - crea las tablas en Supabase (verás líneas `Running migrations` y una por cada tabla con `DONE`);
   - crea tu usuario Dueño;
   - consulta la tasa del BCV;
   - inicia el servidor web.
3. El despliegue termina bien cuando el estado dice **Live** (en verde).
4. Arriba, bajo el nombre del servicio, está la dirección real (`https://....onrender.com`). Si no es igual a la que
   pusiste en `APP_URL`, ve a **Environment**, corrige `APP_URL` y guarda (**Save, rebuild, and deploy**).
5. Abre la dirección y entra con `ADMIN_EMAIL` y `ADMIN_PASSWORD`.

---

## 6. Lista de verificación (en este orden)

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

## 7. Cuando algo falla

El motivo casi siempre está en Render → servicio → **Logs**. Busca la primera línea con `ERROR`, `SQLSTATE` o
`Exception`.

| Lo que ves | Causa probable | Qué hacer |
|---|---|---|
| `Tenant or user not found` | `DB_USERNAME` sin la parte `.abcdefghijklmnop` | Copia el usuario completo del *Shared pooler* |
| `password authentication failed` | `DB_PASSWORD` mal copiada | Pégala de nuevo, o en Supabase → **Reset database password** y actualízala en Render |
| `could not translate host name` / `Network is unreachable` | Se usó la conexión directa (`db.xxxx.supabase.co`) | Usa el host del **Shared pooler** (`aws-0-...pooler.supabase.com`) |
| `prepared statement ... does not exist` | Puerto 6543 (*Transaction pooler*) | `DB_PORT` debe ser `5432` |
| `No application encryption key has been specified` / `Unsupported cipher` | `APP_KEY` vacía o sin `base64:` | Pega la clave completa del paso 3.1 |
| Página **500 Server Error** y nada en **Logs** | Faltan variables o se agregaron sin volver a desplegar | Revisa **Environment** contra la tabla del paso 4 y guarda con **Save, rebuild, and deploy** |
| Página **419 Page Expired** al entrar | La página de login quedó abierta mucho tiempo, o el servicio se durmió y despertó | Recarga el login y vuelve a entrar; si se repite, busca en **Logs** un error de base de datos |
| La foto se sube pero no se ve | Bucket `fotos` sin *Public* o `AWS_URL` mal escrita | Marca el bucket como público; revisa que `AWS_URL` termine en `/object/public/fotos` |
| Error al subir una foto o al crear un respaldo (`SignatureDoesNotMatch`, `InvalidAccessKeyId`) | Claves S3 o región mal copiadas | Revisa `AWS_*`; si dudas, crea una clave nueva en Supabase |
| El despliegue falla antes de llegar a `Running migrations` | Error al construir la imagen | Copia las últimas 30 líneas del log de construcción y pide ayuda con ellas |

Para cambiar una variable: servicio → **Environment** → edita → **Save, rebuild, and deploy**.

---

## 8. Después: el día a día

- **Actualizar el sistema.** Cada `git push` a `main` hace que Render construya y publique la versión nueva:

  ```bash
  git add .
  git commit -m "qué cambió"
  git push
  ```

  Las migraciones nuevas se aplican solas al arrancar. Los datos no se pierden: viven en Supabase.
- **Render gratis se duerme** tras 15 minutos sin visitas; la primera carga después tarda cerca de un minuto.
  Para evitarlo, crea un monitor gratis en <https://uptimerobot.com> (**Add New Monitor** → tipo *HTTP(s)*) que
  visite `https://TU-DIRECCION.onrender.com/login` cada 10 minutos. Usa `/login` y no `/up`: el login consulta la
  base de datos, y eso también evita que Supabase se pause.
- **Supabase gratis pausa el proyecto tras 7 días sin actividad.** El monitor anterior lo mantiene activo. Si se
  pausa, entra a Supabase y pulsa **Restore project**.
- **Supabase gratis no hace copias de seguridad automáticas.** Crea un respaldo desde el sistema con regularidad
  (el Inicio le avisa al Dueño si pasan más de 7 días) y guarda el archivo descargado fuera del servidor: Drive, correo o
  memoria USB.
- **El disco de Render se borra en cada reinicio.** Por eso las fotos y los respaldos van a Supabase. Los archivos
  que subas para restaurar solo duran hasta que confirmes la restauración.

---

## 9. Seguridad

- El repositorio es **público**. No subas el `.env` ni ninguna clave; las claves van solo en el panel de Render.
- En producción no se ejecuta `DatosInicialesSeeder` (el que crea el usuario de prueba `admin@primosramirez.com`
  con contraseña `12345678`). El Dueño se crea con `ADMIN_EMAIL` / `ADMIN_PASSWORD`.
- `APP_DEBUG` debe seguir en `false`: con `true` los errores muestran datos internos a cualquiera.
- Si alguna clave se filtra (la pegaste en un chat, en un archivo del repositorio, etc.), cámbiala: en Supabase
  crea una clave S3 nueva o cambia la contraseña de la base de datos, y actualiza la variable en Render.
