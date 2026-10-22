FROM php:8.4-apache
RUN apt-get update && apt-get install -y --no-install-recommends libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite mbstring \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*
ENV APACHE_DOCUMENT_ROOT=/var/www/pilot/public
ENV CHITS_DB_PATH=/data/chits.sqlite
ENV CHITS_COOKIE_SAMESITE=None
RUN sed -ri 's!/var/www/html!/var/www/pilot/public!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
COPY pilot/ /var/www/pilot/
COPY deploy/backend/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY deploy/backend/entrypoint.sh /usr/local/bin/chits-entrypoint
RUN chmod 0755 /usr/local/bin/chits-entrypoint && mkdir -p /data && chown -R www-data:www-data /var/www/pilot /data
EXPOSE 80
ENTRYPOINT ["/usr/local/bin/chits-entrypoint"]
