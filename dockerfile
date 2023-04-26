# Use an official PHP runtime as a parent image
FROM php:8.1-fpm-alpine

# Set the working directory to /app
WORKDIR /app

# Copy composer.json and composer.lock into the container
COPY composer.json composer.lock /app/

RUN apk add --update --no-cache linux-headers
RUN apk add --no-cache icu-dev
RUN apk add --no-cache libzip-dev

# Install dependencies
RUN apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        curl \
        git \
        icu-dev \
        libzip-dev \
        && pecl install xdebug \
        && docker-php-ext-enable xdebug \
        && docker-php-ext-install intl pdo_mysql zip \
        && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
        && composer install --no-scripts --no-autoloader \
        && apk del .build-deps \
        && rm -rf /tmp/* /var/cache/apk/* /root/.composer

# Copy the rest of the application code into the container
COPY . /app

# Generate the autoload files
RUN composer dump-autoload --optimize

# Copy the .env file into the container
COPY .env /app/.env

# Set up the environment
ENV APP_ENV=local
ENV APP_DEBUG=true
ENV APP_URL=http://localhost
ENV DB_CONNECTION=mysql
ENV DB_HOST=database
ENV DB_PORT=3306
ENV DB_DATABASE=laravel
ENV DB_USERNAME=root
ENV DB_PASSWORD=

# Expose port 9000 and start the PHP-FPM process
EXPOSE 9000
CMD ["php-fpm"]
