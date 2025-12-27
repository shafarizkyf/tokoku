FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git unzip curl libpng-dev libonig-dev libxml2-dev zip libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Copy everything including artisan
COPY . .

# Install composer dependencies
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist

# Permissions and caches
RUN chown -R www-data:www-data storage bootstrap/cache \
 && php artisan config:cache \
 && php artisan route:cache

EXPOSE 9000
CMD ["php-fpm"]
