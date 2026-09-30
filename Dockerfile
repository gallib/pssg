FROM php:8.5-apache

RUN a2enmod headers
RUN a2enmod rewrite

RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip libzip-dev \
    && docker-php-ext-install zip \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && rm -rf /var/lib/apt/lists/*

COPY docker/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini
COPY docker/php-dev.ini /usr/local/etc/php/conf.d/php-dev.ini

WORKDIR /var/www/html

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./

RUN composer install

COPY . .

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf