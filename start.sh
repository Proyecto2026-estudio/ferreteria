#!/bin/sh
set -e

# Dejar un solo MPM activo (evita "More than one MPM loaded" en Railway)
rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.*
[ -f /etc/apache2/mods-enabled/mpm_prefork.load ] || a2enmod mpm_prefork >/dev/null

# Railway indica en $PORT el puerto donde tiene que escuchar la app
PORT="${PORT:-80}"
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Crea las tablas y el catálogo la primera vez (en segundo plano, así Apache arranca enseguida)
php /var/www/html/db/init.php &

exec apache2-foreground
