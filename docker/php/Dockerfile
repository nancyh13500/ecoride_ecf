FROM php:8.2-apache

# Installation des dépendances système
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    unzip \
    git \
    pkg-config \
    libssl-dev \
    libcurl4-openssl-dev \
    && rm -rf /var/lib/apt/lists/*

# Installation des extensions PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        mysqli \
        gd \
        mbstring \
        zip

# Installation de l'extension MongoDB PHP
RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copie de la configuration Apache
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# Activation du module rewrite Apache
RUN a2enmod rewrite headers

# Configuration des permissions (le code sera monté via volume depuis /home/docker/web/ecoride_ecf)
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Exposition du port
EXPOSE 80

# Commande de démarrage : installe Composer si nécessaire puis démarre Apache
CMD ["sh", "-c", "cd /var/www/html && echo '=== Diagnostic ===' && echo 'Répertoire: $(pwd)' && echo 'Contenu:' && ls -la && echo 'index.php existe:' && test -f index.php && echo 'OUI' || echo 'NON' && echo 'composer.json existe:' && test -f composer.json && echo 'OUI' || echo 'NON' && if [ -f composer.json ] && [ ! -f vendor/autoload.php ]; then echo 'Installation Composer...' && composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev; fi && echo 'Démarrage Apache...' && apache2-foreground"]

