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

FROM php:8.3-apache AS base
RUN echo "ServerName 127.0.0.1" >> /etc/apache2/apache2.conf \
&& mkdir -p /var/www/html/public /var/www/html/resources /var/www/db \
&& a2enmod rewrite \
&& pecl install \
redis \
&& docker-php-ext-install \
mysqli \
bcmath \
&& docker-php-ext-enable \
mysqli \
redis

# Install Composer
COPY --from=composer:lts /usr/bin/composer /usr/bin/composer

# Copy custom ini files into conf.d
COPY ./.docker/php/*.ini /usr/local/etc/php/conf.d/

# Copy source code
COPY ./src /var/www/html
# Copy public directory
COPY ./public /var/www/html/public
# Copy resources directory
COPY ./resources /var/www/html/resources

# Create and set permissions for directories
RUN mkdir -p /var/www/html/resources/views-cache \
    && chown -R www-data:www-data /var/www/html/resources \
    && chmod -R 775 /var/www/html/resources \
    && chown -R www-data:www-data /var/www/html/public/assets \
    && chmod -R 775 /var/www/html/public/assets \
    # Copy the favicon.ico
    && favicon=$(find ./public/ -maxdepth 1 -name 'favicon.ico' | head -n 1) && \
    if [ -n "$favicon" ]; then cp "$favicon" /var/www/html/; else echo "No favicon file found."; fi

# Add entrypoint script
COPY ./.docker/scripts/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]

FROM base AS development
COPY ./tests /var/www/html/tests
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini" \
&& pecl install xdebug \
&& docker-php-ext-enable xdebug
# don't need to copy the xdebug.ini file here since it's mounted in the compose file
#COPY ./.docker/php/xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini 
COPY --from=dev-deps app/vendor/ /var/www/html/vendor

FROM development AS test
WORKDIR /var/www/html
RUN ./vendor/bin/phpunit tests/HelloWorldTest.php

FROM base AS final
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY --from=prod-deps app/vendor/ /var/www/html/vendor