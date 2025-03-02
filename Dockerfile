# syntax=docker/dockerfile:1

FROM composer:lts AS dev-deps
WORKDIR /app
RUN --mount=type=bind,source=./composer.json,target=composer.json \
    --mount=type=bind,source=./composer.lock,target=composer.lock \
    --mount=type=cache,target=/tmp/cache \
    composer install --no-interaction

FROM composer:lts AS prod-deps
WORKDIR /app
RUN --mount=type=bind,source=composer.json,target=composer.json \
    --mount=type=bind,source=composer.lock,target=composer.lock \
    --mount=type=cache,target=/tmp/cache \
    composer install --no-dev --no-interaction

FROM php:8.2-apache AS base
RUN echo "ServerName 127.0.0.1" >> /etc/apache2/apache2.conf \
    && mkdir -p /var/www/html/public \
    && mkdir -p /var/www/html/resources && chmod 777 /var/www/html/resources \
    && mkdir -p /var/www/html/tests \
    && a2enmod rewrite \
    && docker-php-ext-install \
        mysqli \
    && docker-php-ext-enable mysqli

# Copy source code
COPY ./src /var/www/html
# Copy .htaccess
COPY ./.docker/apache/.htaccess /var/www/html
# Copy public directory
COPY ./public /var/www/html/public
# Copy resources directory
COPY ./resources /var/www/html/resources
# Give permission to write into uploads directory
RUN chown www-data:www-data /var/www/html/public/assets/uploads \
    && chmod 775 /var/www/html/public/assets/uploads \
    # Copy the favicon.ico
    && favicon=$(find ./public/ -maxdepth 1 -name 'favicon.ico' | head -n 1) && \
    if [ -n "$favicon" ]; then cp "$favicon" /var/www/html/; else echo "No favicon file found."; fi

FROM base AS development
# Install Composer in the development image
COPY --from=composer:lts /usr/bin/composer /usr/bin/composer
COPY ./tests /var/www/html/tests
COPY ./composer.json /var/www/html/
COPY ./composer.lock /var/www/html/
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install zip \
    && mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini" \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug

# Copy tests directory and composer files
# COPY ./tests /var/www/html/tests
# COPY ./composer.json /var/www/html/
# COPY ./composer.lock /var/www/html/

# RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini" \
#     && pecl install xdebug \
#     && docker-php-ext-enable xdebug

# Copy vendor directory from dev-deps stage
COPY --from=dev-deps /app/vendor/ /var/www/html/vendor/

# Ensure vendor directory has proper permissions
RUN chmod -R 755 /var/www/html/vendor

FROM development AS test
WORKDIR /var/www/html
# Verify phpunit is available and run tests
RUN ls -la vendor/bin/ && \
    ./vendor/bin/phpunit --version && \
    ./vendor/bin/phpunit tests/RouteConfigTest.php

FROM base AS final
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY --from=prod-deps /app/vendor/ /var/www/html/vendor/
USER www-data