FROM php:8.1.0-fpm

WORKDIR /var/www/html

# Copy composer.lock and composer.json
COPY laravel-app/composer.lock laravel-app/composer.json /var/www/html/

# Install dependencies
RUN apt-get update -y && apt-get install -y \
    libicu-dev \
    libmariadb-dev \
    unzip zip \
    zlib1g-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    && docker-php-ext-install zip \
    && docker-php-ext-install gettext intl pdo_mysql gd

RUN docker-php-ext-configure gd --enable-gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd

# Install Node.js
RUN curl -sL https://deb.nodesource.com/setup_14.x | bash -
RUN apt-get install -y nodejs

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy the application code
COPY laravel-app /var/www/html

# Set permissions for Laravel directories
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Install dependencies
RUN composer install

# # Generate the Laravel application key
RUN php artisan key:generate

# Clear the Laravel config cache
RUN php artisan config:cache

# Clear the route cache
RUN php artisan route:cache

# Clear the view cache
RUN php artisan view:cache

RUN php artisan migrate