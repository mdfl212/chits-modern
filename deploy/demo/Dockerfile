FROM php:8.4-cli
RUN apt-get update && apt-get install -y --no-install-recommends libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite mbstring \
    && rm -rf /var/lib/apt/lists/*
WORKDIR /app
COPY . /app
ENV CHITS_DB_PATH=/data/chits.sqlite
RUN mkdir -p /data && chown -R www-data:www-data /data
USER www-data
EXPOSE 8080
CMD ["php", "-S", "0.0.0.0:8080", "-t", "/app", "/app/pilot/router.php"]
