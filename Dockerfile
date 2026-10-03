FROM php:8.4-apache

# Dependencias del sistema requeridas
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Extensiones PHP necesarias para Laravel
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Extensión phpredis
RUN pecl install redis && docker-php-ext-enable redis

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Apache: mod_rewrite y vhost apuntando a public/
RUN a2enmod rewrite
COPY docker/apache/default.conf /etc/apache2/sites-available/000-default.conf

# Entrypoint: adapta Apache a $PORT (Render) o 80 (local)
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www

# Copiar la aplicación y las dependencias
COPY . /var/www

RUN composer install --no-dev --optimize-autoloader --no-interaction

# Directorios que Laravel necesita en tiempo de ejecución
RUN mkdir -p \
    bootstrap/cache \
    storage/framework/views \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/testing \
    storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]