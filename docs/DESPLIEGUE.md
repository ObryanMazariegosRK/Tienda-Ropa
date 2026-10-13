# Despliegue en producción

Guía para publicar la tienda en un servidor propio (VPS) con **Docker** y **Coolify**.
Está escrita para una máquina virtual de **Azure for Students**, pero desde el paso 5 sirve
para cualquier VPS con Ubuntu (Oracle Cloud, DigitalOcean, Hetzner...).

> Los precios y pantallas de Azure y Coolify cambian con el tiempo: si algo no coincide
> exactamente, busca la opción con un nombre parecido.

## Cómo queda en el servidor

`docker-compose.prod.yml` levanta 4 contenedores:

| Servicio | Qué hace |
|---|---|
| `web` | nginx: recibe las visitas, entrega CSS/JS/fotos y pasa lo demás a PHP. Es el **único** que recibe tráfico (Coolify le pone el dominio y el HTTPS). |
| `app` | PHP-FPM con Laravel. Al arrancar cachea la configuración y corre las migraciones. |
| `scheduler` | La misma imagen que `app`, ejecutando `schedule:work`: cierra las subastas vencidas cada minuto. |
| `mysql` | La base de datos. Sin puertos abiertos hacia internet. |

Datos permanentes (sobreviven a cada actualización):
- volumen `storage`: fotos subidas, logs y caché de Laravel.
- volumen `mysql-data`: la base de datos.

El código **no** se monta desde una carpeta como en desarrollo: va dentro de la imagen
(`docker/production/Dockerfile`), así que cada despliegue construye una imagen nueva.

---

## Antes de empezar: junta estos datos

Los vas a pegar en Coolify (paso 7). Ninguno se sube al repositorio.

| Variable | De dónde sale |
|---|---|
| `APP_KEY` | En tu computadora: `docker compose exec app php artisan key:generate --show`. Genérala **una sola vez** y guárdala: cifra las contraseñas de las cuentas de correo del panel. |
| `DB_PASSWORD`, `DB_ROOT_PASSWORD` | Inventa dos contraseñas largas y distintas (por ejemplo, de un gestor de contraseñas). Nunca `root`. |
| `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`, `VAPID_SUBJECT` | Cópialas de tu `.env` local. |
| `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` | Cópialas de tu `.env` local. |
| `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` | El Gmail de la tienda y su contraseña de aplicación (16 letras, sin espacios). |

La lista completa con explicaciones está en `.env.production.example`.

---

## Paso 1. Activar Azure for Students

1. Entra a <https://azure.microsoft.com/free/students> y elige **Activar ahora**.
2. Inicia sesión con tu correo de la universidad y verifica que eres estudiante.
   **No pide tarjeta de crédito.**
3. Al terminar tendrás una suscripción **"Azure for Students"** con crédito (unos 100 USD
   al año). Puedes ver cuánto te queda en <https://www.microsoftazuresponsorships.com/balance>.

Si se acaba el crédito, los recursos se detienen; no te cobran.

## Paso 2. Crear la máquina virtual

En <https://portal.azure.com> → **Máquinas virtuales** → **Crear** → **Máquina virtual de Azure**.

**Pestaña "Datos básicos":**

| Campo | Valor |
|---|---|
| Suscripción | Azure for Students |
| Grupo de recursos | Crear nuevo: `amerishop` |
| Nombre de la máquina virtual | `amerishop-vm` |
| Región | La más cercana que permita tu suscripción (por ejemplo *(US) South Central US* o *(US) East US 2*) |
| Opciones de disponibilidad | No se requiere redundancia de infraestructura |
| Tipo de seguridad | Estándar |
| Imagen | **Ubuntu Server 24.04 LTS - x64 Gen2** |
| Tamaño | **Standard_B2s** (2 vCPU, 4 GB RAM; aprox. 30 USD/mes). Más barato: Standard_B1ms (1 vCPU, 2 GB; aprox. 15 USD/mes), justo para Coolify + la tienda. |
| Tipo de autenticación | Clave pública SSH |
| Nombre de usuario | `azureuser` |
| Origen de clave pública SSH | Generar nuevo par de claves (RSA), nombre `amerishop-clave` |
| Puertos de entrada públicos | Permitir los puertos seleccionados: **SSH (22), HTTP (80), HTTPS (443)** |

**Pestaña "Discos":** SSD estándar, 30 GB está bien (puedes subir a 64 GB).

**Revisar y crear** → **Crear**. Te pedirá **descargar la clave privada** (`amerishop-clave.pem`):
descárgala y guárdala bien. Es la única forma de entrar al servidor y **no se puede
volver a descargar**.

## Paso 3. Darle un nombre (dirección) al servidor

Para tener HTTPS sin comprar dominio, Azure da un nombre gratis:

1. Abre la máquina virtual → en "Información general", haz clic en la **Dirección IP pública**.
2. **Configuración** → **Etiqueta de nombre DNS**: escribe `amerishop` (o una variante si ya
   está usada) → **Guardar**.
3. En la misma pantalla, cambia la asignación de la IP a **Estática** (así no cambia si
   reinicias la máquina).

Tu tienda vivirá en algo como `https://amerishop.southcentralus.cloudapp.azure.com`.
Si después compras un dominio, solo se cambia en Coolify y en `APP_URL`.

## Paso 4. Abrir el puerto del panel de Coolify (temporal)

El panel de Coolify se abre en el puerto 8000 hasta que le pongas su propio dominio.

Máquina virtual → **Redes** (o *Configuración de red*) → **Crear regla de puerto** →
**Regla de puerto de entrada**: puerto de destino `8000`, protocolo TCP, nombre `coolify`.

> Cuando termines la configuración (paso 6), puedes cerrar este puerto y entrar al panel
> por un dominio con HTTPS.

## Paso 5. Conectarte al servidor por SSH

Desde **WSL/Ubuntu** en tu computadora (la clave debe tener permisos restringidos):

```bash
mkdir -p ~/.ssh
cp /mnt/c/Users/<tu-usuario-de-windows>/Downloads/amerishop-clave.pem ~/.ssh/
chmod 600 ~/.ssh/amerishop-clave.pem
ssh -i ~/.ssh/amerishop-clave.pem azureuser@<IP-pública-del-servidor>
```

La primera vez pregunta si confías en el servidor: escribe `yes`.

Ya dentro, actualiza el sistema y crea un archivo de intercambio (*swap*) de 4 GB, que
evita que el servidor se quede sin memoria al construir las imágenes:

```bash
sudo apt update && sudo apt upgrade -y
sudo fallocate -l 4G /swapfile && sudo chmod 600 /swapfile
sudo mkswap /swapfile && sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
free -h   # debe mostrar Swap: 4.0Gi
```

## Paso 6. Instalar Coolify

En el servidor:

```bash
curl -fsSL https://cdn.coollabs.io/coolify/install.sh | sudo bash
```

Tarda unos minutos (instala Docker y Coolify). Al terminar, abre en tu navegador
`http://<IP-pública>:8000` y **crea tu cuenta de inmediato**: la primera persona que se
registra se vuelve la dueña del panel.

En el asistente inicial elige **This Machine / localhost** como servidor.

## Paso 7. Desplegar la tienda

1. **Conectar GitHub:** si el repositorio es privado, en Coolify → *Sources* → agrega una
   **GitHub App** y dale acceso al repositorio. (Si es público, no hace falta.)
2. **Projects** → *+ Add* → nombre `Amerishop` → entra al entorno *production* →
   **+ New resource** → *Private Repository (with GitHub App)* (o *Public Repository*).
3. Elige el repositorio y la rama `main`.
4. **Build Pack: Docker Compose**. Docker Compose Location: `/docker-compose.prod.yml`.
5. En la lista de servicios, en **`web`** → *Domains*, escribe tu dirección **con https**:
   `https://amerishop.southcentralus.cloudapp.azure.com`. Deja los demás servicios sin dominio.
6. **Environment Variables:** pega las variables de "Antes de empezar" y además:
   ```
   APP_URL=https://amerishop.southcentralus.cloudapp.azure.com
   GOOGLE_REDIRECT_URI=https://amerishop.southcentralus.cloudapp.azure.com/auth/google/callback
   ```
   (con tu dirección real).
7. **Deploy**. La primera vez tarda varios minutos (construye las imágenes). En *Logs* verás
   las migraciones y al final `ready to handle connections`.
8. Abre tu dirección: deberías ver la tienda con candado (HTTPS). Coolify genera el
   certificado de Let's Encrypt solo; si al principio dice "no seguro", espera un minuto
   y recarga.

## Paso 8. Crear el primer administrador

1. Regístrate en la tienda como cualquier cliente (o entra con Google) y verifica el correo.
2. En Coolify → el recurso → **Terminal** → elige el contenedor **app** y ejecuta:
   ```bash
   php artisan users:make-admin tu-correo@gmail.com
   ```
3. Entra a `/admin/login`.

Para quitarle el rol a alguien: `php artisan users:make-admin correo@... --revoke`.

## Paso 9. Ajustes finales

- **Google Cloud Console** → Credenciales → tu cliente OAuth:
  - *URI de redireccionamiento autorizados*: agrega
    `https://<tu-dirección>/auth/google/callback`.
  - *Orígenes de JavaScript autorizados*: `https://<tu-dirección>`.
  - *Pantalla de consentimiento*: enlace a la política de privacidad
    `https://<tu-dirección>/politica-de-privacidad` (revisa antes el texto, es un borrador).
- **Correo:** en el panel → *Configuración → Correo de envío* agrega la cuenta de la tienda y
  envía un correo de prueba.
- **Notificaciones push:** entra como cliente → *Mi perfil* → activa las notificaciones.

---

## Actualizar la tienda

1. Haz `git push` a `main`.
2. En Coolify → **Redeploy** (o activa *Automatic deployment* en la configuración del recurso
   para que se despliegue solo con cada push).

Cada despliegue construye imágenes nuevas y corre las migraciones pendientes. Las fotos y la
base de datos se conservan (están en volúmenes).

## Respaldos

Lo importante es la **base de datos** y las **fotos**. Desde el servidor por SSH:

```bash
# Base de datos
MYSQL=$(sudo docker ps --filter "name=mysql" --format "{{.Names}}" | head -1)
sudo docker exec "$MYSQL" sh -c 'mysqldump -u root -p"$MYSQL_ROOT_PASSWORD" --single-transaction tienda_ropa' \
  | gzip > respaldo-$(date +%F).sql.gz

# Fotos subidas
APP=$(sudo docker ps --filter "name=app" --format "{{.Names}}" | head -1)
sudo docker exec "$APP" tar czf - -C /var/www/storage app/public > fotos-$(date +%F).tar.gz
```

Descarga los archivos a tu computadora (desde WSL):
`scp -i ~/.ssh/amerishop-clave.pem azureuser@<IP>:~/respaldo-*.sql.gz .`

Hazlo al menos una vez por semana y siempre antes de cambios grandes.

## Mantenimiento útil (Terminal de Coolify, contenedor `app`)

```bash
php artisan images:clean-orphans            # lista fotos sin usar (--delete para borrarlas)
php artisan auctions:close-expired          # cerrar subastas vencidas a mano
php artisan migrate:status                  # ver migraciones aplicadas
```

## Costos y cómo pausar

- El crédito se gasta mientras la máquina está **encendida**. Si no la usarás por un tiempo:
  portal → máquina virtual → **Detener** (queda "desasignada" y deja de cobrar la CPU; el disco
  y la IP estática siguen costando unos pocos dólares al mes).
- Revisa el saldo de vez en cuando (paso 1).

## Problemas comunes

| Síntoma | Causa probable |
|---|---|
| El despliegue falla con `Falta APP_KEY` / `Falta DB_PASSWORD` | Falta esa variable en *Environment Variables*. |
| La tienda carga pero sin estilos o con enlaces a `localhost` | `APP_URL` mal escrito (debe ser `https://` + tu dirección, sin `/` al final). Redeploy. |
| "Esperando a la base de datos..." muchas veces | MySQL aún arranca (la primera vez tarda) o `DB_PASSWORD` cambió después de crear el volumen: la contraseña queda fija la primera vez que se crea `mysql-data`. |
| El login con Google da error de redirección | Falta la URI en Google Cloud Console o `GOOGLE_REDIRECT_URI` no coincide exactamente. |
| Las subastas no se cierran | El contenedor `scheduler` no está corriendo: revisa sus logs en Coolify. |
| El servidor se pone muy lento al desplegar | Poca memoria: confirma el swap (`free -h`) o usa un tamaño de máquina mayor. |
