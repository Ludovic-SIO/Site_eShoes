# Étape 1 : Image de base PHP avec extensions nécessaires
FROM php:8.2-apache

# Active les modules Apache nécessaires
RUN a2enmod rewrite

# Installe les dépendances système et extensions PHP
RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libzip-dev libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install intl pdo pdo_mysql zip gd opcache

# Copie les fichiers du projet
WORKDIR /var/www/html
COPY . /var/www/html

# Installe Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Installe les dépendances PHP (en production)
RUN composer install --no-dev --optimize-autoloader

# Donne les bons droits
RUN chown -R www-data:www-data /var/www/html/var

# Configure Apache pour Symfony
COPY ./docker/vhost.conf /etc/apache2/sites-available/000-default.conf

# Expose le port 80
EXPOSE 80

# Commande de démarrage
CMD ["apache2-foreground"]
