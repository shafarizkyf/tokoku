FROM php:8.4-fpm

ARG APP_ENV=production
ENV APP_ENV=${APP_ENV}

RUN apt-get update && apt-get install -y \
    git unzip curl libpng-dev libonig-dev libxml2-dev zip libzip-dev \
    libicu-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Copy application source
COPY . .

# Install Composer
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

RUN if [ "$APP_ENV" = "production" ]; then \
      composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --prefer-dist \
        --optimize-autoloader; \
    else \
      composer install; \
    fi

# Prepare Laravel writable dirs
RUN set -eux; \
    mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views; \
    chown -R www-data:www-data storage bootstrap/cache; \
    chmod -R 775 storage bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
