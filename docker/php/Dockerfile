FROM php:8.2-apache

# Variables d'environnement pour éviter les interactions
ENV DEBIAN_FRONTEND=noninteractive

# Installation des dépendances système
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
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
    bash \
    default-mysql-client && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Installation des extensions PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        mysqli \
        gd \
        mbstring \
        zip

# Installation de l'extension MongoDB PHP
RUN pecl install mongodb && \
    docker-php-ext-enable mongodb

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copie de la configuration Apache
COPY ./docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# Activation des modules Apache
RUN a2enmod rewrite headers

# Copie du script d'initialisation
COPY ./docker/php/init-db.sh /usr/local/bin/init-db.sh
RUN sed -i 's/\r$//' /usr/local/bin/init-db.sh && \
    chmod +x /usr/local/bin/init-db.sh

# Copie du code de l'application
COPY . /var/www/html/

# Configuration des permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

EXPOSE 80

# Le CMD est défini dans docker-compose.yml