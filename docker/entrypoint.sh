#!/usr/bin/env bash
set -e

# Render inyecta la variable $PORT (default 10000). Local = 80.
PORT="${PORT:-80}"

sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s@<VirtualHost \*:[0-9]*>@<VirtualHost *:${PORT}>@" /etc/apache2/sites-available/000-default.conf

exec "$@"