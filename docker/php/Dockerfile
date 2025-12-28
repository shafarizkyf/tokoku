FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git unzip curl libpng-dev libonig-dev libxml2-dev zip libzip-dev \
    libicu-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Copy everything including artisan
COPY . .

# Install composer dependencies
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist

# Ensure storage/framework subfolders exist and are writable
# Ensure storage/framework subfolders exist and are writable
RUN set -eux; \
    mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views; \
    chown -R www-data:www-data storage bootstrap/cache

RUN php artisan config:cache \
 && php artisan route:cache \
 && php artisan view:cache \
 && php artisan storage:link

EXPOSE 9000
CMD ["php-fpm"]
