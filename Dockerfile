FROM php:8.4-apache

# Instalar dependencias del sistema requeridas
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev

# Limpiar caché de apt
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar extensiones de PHP necesarias para Laravel
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Instalar la extensión de Redis para PHP
RUN pecl install redis && docker-php-ext-enable redis

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instalar Node.js (versión 22 LTS) y npm
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs

# Configurar Apache para Laravel: mod_rewrite y sitio apuntando a public/
RUN a2enmod rewrite
COPY docker/apache/default.conf /etc/apache2/sites-available/000-default.conf

# Configurar el directorio de trabajo
WORKDIR /var/www