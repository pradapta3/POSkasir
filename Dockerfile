# syntax=docker/dockerfile:1

# ---- 1. Aset front-end (Vite/Tailwind) ----
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package.json ./
RUN npm install
COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm run build

# ---- 2. Image aplikasi (PHP-FPM + Nginx + Supervisor) ----
FROM php:8.3-fpm-alpine AS app

RUN apk add --no-cache nginx supervisor bash mysql-client tzdata \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS linux-headers \
        libpng-dev libjpeg-turbo-dev freetype-dev \
        libzip-dev icu-dev oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql gd zip intl mbstring bcmath opcache \
    && apk add --no-cache libpng libjpeg-turbo freetype libzip icu oniguruma \
    && apk del --no-cache .build-deps

# Composer dijalankan di image ini juga (bukan di image `composer:2`
# terpisah) supaya ekstensi PHP yang diperiksa composer — ext-gd, ext-zip,
# ext-intl, dst — persis sama dengan yang tersedia saat aplikasi berjalan.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev --no-scripts --no-interaction --no-progress \
    --prefer-dist --optimize-autoloader

COPY . .
COPY --from=frontend /app/public/build ./public/build

# Autoloader di atas dibuat saat app/ dan database/ belum ada, jadi
# classmap-nya masih kosong — dibangun ulang setelah kode lengkap tersalin.
RUN composer dump-autoload --no-dev --no-scripts --optimize

COPY docker/php.ini /usr/local/etc/php/conf.d/99-poskasir.ini
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80
ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
