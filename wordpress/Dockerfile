FROM wordpress:latest

USER root

RUN set -eux; \
    apt-get update; \
    apt-get upgrade -y; \
    apt-get install -y --no-install-recommends curl ca-certificates; \
    rm -rf /var/lib/apt/lists/*; \
    a2enmod headers rewrite; \
    sed -ri 's/^Listen 80$/Listen 8080/' /etc/apache2/ports.conf; \
    sed -ri 's/<VirtualHost \*:80>/<VirtualHost *:8080>/' /etc/apache2/sites-available/000-default.conf; \
    mkdir -p /usr/src/wordpress/wp-content/mu-plugins; \
    rm -f /usr/src/wordpress/readme.html /usr/src/wordpress/license.txt; \
    rm -rf /usr/src/wordpress/wp-content/plugins/akismet /usr/src/wordpress/wp-content/plugins/hello.php; \
    find /usr/src/wordpress/wp-content/themes -name readme.txt -delete; \
    install -d -o www-data -g www-data /var/www/html /var/run/apache2 /var/lock/apache2 /var/log/apache2; \
    chown -R www-data:www-data /var/www/html /var/run/apache2 /var/lock/apache2 /var/log/apache2

COPY apache-hardening.conf /etc/apache2/conf-available/zz-security-hardening.conf
COPY php-hardening.ini /usr/local/etc/php/conf.d/security-hardening.ini
COPY wp-hardening.php /usr/src/wordpress/wp-content/mu-plugins/wp-hardening.php

RUN set -eux; \
    a2enconf zz-security-hardening; \
    chown www-data:www-data /usr/src/wordpress/wp-content/mu-plugins/wp-hardening.php

EXPOSE 8080

USER www-data
