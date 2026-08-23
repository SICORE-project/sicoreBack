FROM php:8.2-fpm-alpine

# Installer les dépendances système et extensions PHP requises
RUN apk add --no-cache unzip libpq-dev libpng-dev libjpeg-turbo-dev freetype-dev
RUN docker-php-ext-install pdo pdo_mysql gd

# Récupérer Composer depuis l'image officielle
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copier l'intégralité du code de l'application
COPY . .

# Installer les dépendances PHP sans les outils de développement
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Configurer les permissions indispensables pour Laravel
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
