FROM php:8.2-apache

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

# Copie du code de l'application
COPY . /var/www/html/

# Installation des dépendances Composer
RUN cd /var/www/html && \
    if [ -f composer.json ]; then \
        composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev && \
        chown -R www-data:www-data vendor; \
    fi

# Configuration des permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

EXPOSE 80