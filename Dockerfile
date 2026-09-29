# Ferretería Equipo 8 — imagen para Railway (PHP 8.2 + Apache + MySQLi)
FROM php:8.2-apache

RUN docker-php-ext-install mysqli

# Un solo MPM (prefork) para evitar "More than one MPM loaded"
RUN rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.* \
    && a2enmod mpm_prefork rewrite headers

# Railway está detrás de un proxy HTTPS: avisar a PHP que la conexión original es https
RUN printf 'SetEnvIf X-Forwarded-Proto "^https$" HTTPS=on\n' > /etc/apache2/conf-enabled/forwarded-https.conf

COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

CMD ["/usr/local/bin/start.sh"]
