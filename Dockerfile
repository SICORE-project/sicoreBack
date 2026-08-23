FROM php:8.2-fpm-alpine

# Installer les dépendances système et extensions PHP requises
RUN apk add --no-cache unzip libpq-dev libpng-dev libjpeg-turbo-dev freetype-dev
RUN docker-php-ext-install pdo pdo_mysql gd

# Récupérer Composer depuis l'image officielle
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# CORRECTION : On force la copie des fichiers de configuration en premier
COPY composer.json composer.lock* ./

# Installer les dépendances PHP
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Copier le reste de l'application
COPY . .

# Configurer les permissions indispensables pour Laravel
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
