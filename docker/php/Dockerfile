FROM php:8.2-apache

# Forcer l'utilisation de miroirs Debian fiables
RUN echo "deb http://deb.debian.org/debian bookworm main" > /etc/apt/sources.list && \
    echo "deb http://deb.debian.org/debian bookworm-updates main" >> /etc/apt/sources.list && \
    echo "deb http://security.debian.org/debian-security bookworm-security main" >> /etc/apt/sources.list

# Installation des dépendances système avec retry
RUN apt-get clean && \
    apt-get update --fix-missing || apt-get update || true && \
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
    default-mysql-client \
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

# Copie du code de l'application
COPY . /var/www/html/

# Copie du script d'initialisation
COPY docker/php/init-db.sh /usr/local/bin/init-db.sh
RUN sed -i 's/\r$//' /usr/local/bin/init-db.sh && \
    chmod +x /usr/local/bin/init-db.sh

# Installation des dépendances Composer
RUN if [ -f /var/www/html/composer.json ]; then \
        cd /var/www/html && \
        composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev; \
    fi

# Configuration des permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80

CMD ["sh", "-c", "/usr/local/bin/init-db.sh && apache2-foreground"]