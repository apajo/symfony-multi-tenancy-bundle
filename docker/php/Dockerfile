# Runner image for the bundle's test suite.
#
# PHP 8.2 matches composer.json's `php: ^8.2` and the locked dependency set --
# notably doctrine/dbal 3.8.5, which DatabaseAdapter needs for its
# Connection::__construct()/getEventManager() reconnect trick.
FROM php:8.2-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libicu-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql intl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /app

ENTRYPOINT ["entrypoint.sh"]
CMD ["vendor/bin/phpunit"]
