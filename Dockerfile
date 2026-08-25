# Image PHP-FPM + Nginx pour servir le frontend Laravel SICORE.
# Le frontend ne compile pas d'assets avec Node: les CSS/JS sont deja dans public/assets.

# Étape 1 : Installation des dépendances avec une image PHP complète
FROM php:8.2-alpine AS vendor
WORKDIR /app

# Installation de Composer et des outils système requis pour extraire les paquets
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN apk add --no-cache git unzip libzip-dev oniguruma-dev icu-dev \
    && docker-php-ext-install zip mbstring intl

# Copie des fichiers de configuration de paquets (inclure le .lock si existant)
COPY composer.json composer.lock* ./

# Installation des dépendances (sans les scripts pour le moment)
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress --optimize-autoloader --no-scripts

# Copie du reste du code de l'application
COPY . .

# Génération finale de l'autoloader optimisé
RUN composer dump-autoload --optimize

# Étape 2 : Image finale de production
FROM php:8.2-fpm-alpine
WORKDIR /var/www/html

# Extensions PHP utiles a Laravel et au client API.
RUN apk add --no-cache nginx supervisor bash icu-dev libzip-dev oniguruma-dev \
    && docker-php-ext-install intl mbstring pdo pdo_mysql zip opcache

# Récupération du code propre depuis l'étape vendor
COPY --from=vendor /app /var/www/html

# Configuration des services
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf

# Laravel doit pouvoir ecrire dans storage et bootstrap/cache.
# Création des dossiers s'ils n'existent pas pour éviter les erreurs de permissions
RUN mkdir -p storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8080
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
