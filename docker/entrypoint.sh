#!/bin/bash
set -e

# Render inyecta la variable $PORT (default 10000). Local = 80.
PORT="${PORT:-80}"
echo "[entrypoint] PORT=${PORT}"

# Apache debe escuchar en $PORT (Render) o 80 (local)
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s@<VirtualHost \*:[0-9]*>@<VirtualHost *:${PORT}>@" /etc/apache2/sites-available/000-default.conf

# Fallo temprano si la config de Apache es inválida
apache2ctl -t

echo "[entrypoint] iniciando apache2-foreground"
exec "$@"