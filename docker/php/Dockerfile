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

# Installation du client MySQL pour l'import SQL
RUN apt-get update && apt-get install -y default-mysql-client && rm -rf /var/lib/apt/lists/*

# Copie de la configuration Apache
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# Activation du module rewrite Apache
RUN a2enmod rewrite headers

# Copie du code de l'application (index.php, lib/, pages/, etc.)
COPY . /var/www/html/

# Copie du script d'initialisation de la base de données
COPY docker/php/init-db.sh /usr/local/bin/init-db.sh
RUN chmod +x /usr/local/bin/init-db.sh

# Installation des dépendances Composer
RUN if [ -f /var/www/html/composer.json ]; then \
        cd /var/www/html && \
        composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev; \
    fi

# Configuration des permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Exposition du port 80 (interne au conteneur, pas exposé sur l'hôte)
EXPOSE 80

# Démarrage : initialise la base de données puis démarre Apache
CMD ["sh", "-c", "/usr/local/bin/init-db.sh && apache2-foreground"]
