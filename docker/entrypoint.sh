#!/bin/bash
# Todos los mensajes van a STDERR: Render captura stderr aunque el proceso muera rápido
trap 'echo "[entrypoint] ***** saliendo, codigo=$? ($(date -u +%FT%TZ)) *****" >&2' EXIT

echo "[entrypoint] arrancando" >&2
echo "[entrypoint] PORT=${PORT:-<sin PORT>}" >&2
env | grep -E '^(PORT|RENDER|APP|DB_|SESSION|QUEUE|CACHE|MYSQL|RAILWAY|LOG_)' | sed 's/=.*/=<set>/' >&2 || true

ls -ld /var/www /var/www/public 2>&1 >&2 || true

if [ ! -f /var/www/public/index.php ]; then
    echo "[entrypoint] ERROR: /var/www/public/index.php NO existe en la imagen" >&2
    echo "[entrypoint] --- busqueda de index.php ---" >&2
    find / -maxdepth 5 -name index.php -not -path "/proc/*" -not -path "/sys/*" 2>/dev/null >&2 || true
    echo "[entrypoint] --- fin de la busqueda ---" >&2
    exit 1
fi
echo "[entrypoint] OK: /var/www/public/index.php presente" >&2

# Directorios que Apache/Laravel escriben en runtime
mkdir -p bootstrap/cache storage/framework/views storage/framework/cache/data storage/framework/sessions storage/logs
chown -R www-data:www-data storage bootstrap/cache || true

# Apache debe escuchar en $PORT (Render) o 80 (local)
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s@<VirtualHost \*:[0-9]*>@<VirtualHost *:${PORT}>@" /etc/apache2/sites-available/000-default.conf

# Fallo temprano si la config de Apache es inválida
apache2ctl -t

echo "[entrypoint] lanzando CMD" >&2
exec "$@"