# =============================================================================
# Imagen de PRODUCCIÓN (la de desarrollo es el Dockerfile de la raíz).
# Diferencia principal: aquí el código y las dependencias van DENTRO de la imagen;
# solo storage/ (fotos subidas, logs, caché) vive en un volumen permanente.
#
#   target "app": PHP-FPM con Laravel (también lo usa el scheduler)
#   target "web": nginx con los archivos públicos (CSS, JS, imágenes de la tienda)
# =============================================================================

FROM php:8.3-fpm AS app

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libzip-dev libpng-dev libjpeg-dev libwebp-dev libfreetype6-dev libgmp-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install pdo_mysql zip gd exif gmp opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/production/php.ini /usr/local/etc/php/conf.d/zz-produccion.ini
COPY docker/php/www-tienda.conf /usr/local/etc/php-fpm.d/zz-tienda.conf
COPY docker/production/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

WORKDIR /var/www

# Primero solo las dependencias: si composer.lock no cambia, Docker reutiliza esta capa
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist

COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && php artisan package:discover --ansi \
    && mkdir -p storage/app/public storage/app/private storage/framework/cache/data \
                storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && ln -sfn /var/www/storage/app/public public/storage \
    && chown -R www-data:www-data storage bootstrap/cache

# tinker (php artisan tinker) guarda su configuración aquí; la carpeta personal de www-data no es escribible
ENV XDG_CONFIG_HOME=/tmp

USER www-data
ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]


FROM nginx:1.27-alpine AS web

COPY docker/production/nginx.conf /etc/nginx/conf.d/default.conf
# public/ incluye el enlace public/storage -> /var/www/storage/app/public (el volumen de las fotos)
COPY --from=app /var/www/public /var/www/public
