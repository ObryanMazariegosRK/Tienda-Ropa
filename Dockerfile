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
    libfreetype6-dev

RUN docker-php-ext-configure gd --with-freetype --with-jpeg

# Acelera la firma VAPID de las notificaciones push
RUN apt-get update && apt-get install -y libgmp-dev \
    && docker-php-ext-install gmp

RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    zip \
    gd
    
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

CMD ["php-fpm"]