FROM php:8.2-fpm

RUN apt-get update && apt-get install -y libmcrypt-dev libpng-dev libicu-dev libpq-dev libxpm-dev libvpx-dev \
        git nano zlib1g-dev libxml2-dev libzip-dev libxslt-dev unzip dos2unix libmagickwand-dev librabbitmq-dev \
        ca-certificates libgnutls30 libgmp-dev zlib1g-dev libpng-dev libjpeg-dev \
    && docker-php-ext-install -j$(nproc) intl \
    && docker-php-ext-install -j$(nproc) zip \
    && docker-php-ext-install -j$(nproc) pgsql \
    && docker-php-ext-install -j$(nproc) pdo_pgsql \
    && docker-php-ext-install -j$(nproc) xsl \
    && docker-php-ext-install -j$(nproc) mysqli \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd  \
    && docker-php-ext-install -j$(nproc) gmp  \
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    && docker-php-ext-install bcmath

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/backend

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY --chown=app ../ /var/www/backend
