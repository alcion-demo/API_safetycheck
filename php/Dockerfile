FROM php:8.4-fpm-alpine3.21

COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN apk update && apk add --no-cache \
    vim \
    curl \
    zip \
    unzip \
    icu-dev \
    git \
    mariadb-connector-c-dev \
    mariadb-client

RUN docker-php-ext-configure intl
RUN docker-php-ext-install intl pdo_mysql
