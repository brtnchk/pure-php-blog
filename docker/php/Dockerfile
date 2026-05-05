FROM php:8.2-fpm-alpine

RUN apk add --no-cache git unzip libzip-dev icu-dev oniguruma-dev mysql-client \
 && docker-php-ext-install pdo pdo_mysql opcache zip intl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json ./
RUN composer install --no-interaction --no-progress --prefer-dist --no-scripts

COPY . .

RUN chown -R www-data:www-data /var/www/html/templates_c /var/www/html/public/uploads

EXPOSE 9000
CMD ["php-fpm"]
