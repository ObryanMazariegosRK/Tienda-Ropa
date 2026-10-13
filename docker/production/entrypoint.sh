#!/bin/sh
# Se ejecuta cada vez que arranca el contenedor app o el scheduler.
set -e
cd /var/www

if [ -z "$APP_KEY" ]; then
    echo "ERROR: falta la variable APP_KEY. Genérala con: php artisan key:generate --show" >&2
    exit 1
fi

# El volumen de storage puede venir vacío o de una versión anterior
mkdir -p storage/app/public storage/app/private storage/framework/cache/data \
         storage/framework/sessions storage/framework/views storage/logs

# Caché de configuración, rutas y vistas con las variables de este servidor
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Solo el contenedor "app" migra la base (el scheduler no, para no hacerlo dos veces)
if [ "${CONTAINER_ROLE:-app}" = "app" ]; then
    intentos=0
    until php artisan migrate --force; do
        intentos=$((intentos + 1))
        if [ "$intentos" -ge 30 ]; then
            echo "ERROR: no se pudo conectar a la base de datos para migrar." >&2
            exit 1
        fi
        echo "Esperando a la base de datos... ($intentos)"
        sleep 2
    done
fi

exec "$@"
