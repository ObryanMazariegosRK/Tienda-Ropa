FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    zip \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libfreetype6-dev

# Acelera la firma VAPID de las notificaciones push
RUN apt-get update && apt-get install -y libgmp-dev \
    && docker-php-ext-install gmp

# configure y install de gd van en el mismo paso: si se separan, la instalación de otra
# extensión en medio borra el código ya configurado y gd queda sin soporte JPEG/WebP.
# exif: para leer la orientación de las fotos tomadas con celular
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install \
    pdo \
    pdo_mysql \
    zip \
    gd \
    exif
    
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

CMD ["php-fpm"]