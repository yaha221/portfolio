FROM php:8.4-fpm-alpine AS base

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN install-php-extensions \
        intl \
        pdo_pgsql \
        opcache \
        zip \
        gd \
        amqp \
    && apk add --no-cache bash git

ENV COMPOSER_HOME=/tmp/composer

COPY conf.d/app.ini /usr/local/etc/php/conf.d/zz-app.ini

WORKDIR /var/www/backend

# Run as the host user's UID/GID so files created in the bind mount (vendor, generated code) stay editable from WSL
ARG HOST_UID=1000
ARG HOST_GID=1000
RUN addgroup -g ${HOST_GID} app \
    && adduser -D -u ${HOST_UID} -G app app \
    && mkdir -p /var/www/backend/var /tmp/composer \
    && chown -R app:app /var/www/backend /tmp/composer

USER app

FROM base AS dev

USER root
RUN install-php-extensions xdebug pcov \
    && echo "xdebug.mode=off" > /usr/local/etc/php/conf.d/zz-xdebug.ini
USER app
