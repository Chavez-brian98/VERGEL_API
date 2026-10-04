#!/bin/bash
set -e

# Render inyecta la variable $PORT (default 10000). Local = 80.
PORT="${PORT:-80}"
echo "[entrypoint] PORT=${PORT}"

# Diagnóstico: la app debe estar en /var/www/public dentro de la imagen
if [ ! -f /var/www/public/index.php ]; then
    echo "[entrypoint] ERROR: /var/www/public/index.php no existe en la imagen"
    echo "[entrypoint] --- busqueda de index.php ---"
    find / -maxdepth 5 -name index.php -not -path "/proc/*" -not -path "/sys/*" 2>/dev/null || true
    echo "[entrypoint] --- fin de la busqueda ---"
    exit 1
fi
echo "[entrypoint] OK: /var/www/public/index.php presente"

# Directorios que Apache/Laravel escriben en runtime
mkdir -p bootstrap/cache storage/framework/views storage/framework/cache/data storage/framework/sessions storage/logs
chown -R www-data:www-data storage bootstrap/cache || true

# Apache debe escuchar en $PORT (Render) o 80 (local)
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s@<VirtualHost \*:[0-9]*>@<VirtualHost *:${PORT}>@" /etc/apache2/sites-available/000-default.conf

# Fallo temprano si la config de Apache es inválida
apache2ctl -t

echo "[entrypoint] iniciando apache2-foreground"
exec "$@"