#!/bin/sh
set -e

# Railway indica en $PORT el puerto donde tiene que escuchar la app
PORT="${PORT:-80}"
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Crea las tablas y el catálogo la primera vez (si la base está vacía)
php /var/www/html/db/init.php || true

exec apache2-foreground
