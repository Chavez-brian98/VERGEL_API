# Cambia esta fecha para invalidar el caché de capas de la imagen (Render reutiliza capas cacheadas).
# Si alguna vez dudás de que esté corriendo una imagen vieja, bumpéala y redesplegá.
ARG RENDER_CACHE_BUST=2026-10-04-3

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
    && apt-get clean && rm -rf /var/lib/apt/lists/* \
    && echo "cache_bust: ${RENDER_CACHE_BUST}"

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

# Copiar la aplicación
COPY . /var/www

# Directorios que Laravel necesita para composer install y en tiempo de ejecución
RUN mkdir -p \
    bootstrap/cache \
    storage/framework/views \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/testing \
    storage/logs

# Defensa contra contexto incompleto: public/ debe haber llegado a la imagen
RUN test -f /var/www/public/index.php

RUN composer install --no-dev --optimize-autoloader --no-interaction

# Reutilizamos "www-data" los directorios que el proceso Apache escribe
RUN chown -R www-data:www-data storage bootstrap/cache

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/bin/bash", "-c", "set -e; trap 'echo \"[cmd-vergel] saliendo, codigo=$?\" >&2' EXIT; PORT=\"${PORT:-80}\"; echo \"[cmd-vergel] CMD iniciado, PORT=${PORT}\" >&2; sed -i \"s/^Listen .*/Listen ${PORT}/\" /etc/apache2/ports.conf; sed -i \"s@<VirtualHost \\*:[0-9]*>@<VirtualHost *:${PORT}>@\" /etc/apache2/sites-available/000-default.conf; apache2ctl -t; exec apache2-foreground"]