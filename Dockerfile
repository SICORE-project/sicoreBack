# Image PHP-FPM + Nginx pour servir le frontend Laravel SICORE.
# Le frontend ne compile pas d'assets avec Node: les CSS/JS sont deja dans public/assets.
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress --optimize-autoloader --no-scripts
COPY . .
RUN composer dump-autoload --optimize

FROM php:8.2-fpm-alpine
WORKDIR /var/www/html

# Extensions PHP utiles a Laravel et au client API.
RUN apk add --no-cache nginx supervisor bash icu-dev libzip-dev oniguruma-dev \
    && docker-php-ext-install intl mbstring pdo pdo_mysql zip opcache

COPY --from=vendor /app /var/www/html
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf

# Laravel doit pouvoir ecrire dans storage et bootstrap/cache.
RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8080
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
