# Stage 1: Combined PHP + Node builder
# Both runtimes are needed because the Wayfinder Vite plugin calls `php artisan` during `npm run build`
FROM php:8.2-cli-alpine AS builder

RUN apk add --no-cache \
    nodejs \
    npm \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    && docker-php-ext-install pdo_mysql zip intl mbstring

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Install PHP dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Install Node dependencies
COPY package.json package-lock.json ./
RUN npm ci

# Copy application files
COPY . .

# Generate APP_KEY so Laravel can bootstrap, then build frontend
# (wayfinder:generate is called automatically by the Vite plugin during npm run build)
RUN cp .env.example .env \
    && sed -i 's/^APP_ENV=.*/APP_ENV=local/' .env \
    && php artisan key:generate --force \
    && npm run build

# Stage 2: PHP-FPM runtime
FROM php:8.2-fpm-alpine AS app

RUN apk add --no-cache \
    bash \
    curl \
    netcat-openbsd \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql \
        opcache \
        pcntl \
        zip \
        gd \
        exif \
        intl \
        mbstring

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-scripts

COPY . .

# Copy built frontend assets from builder
COPY --from=builder /app/public/build ./public/build

# Snapshot public/ so the entrypoint can sync it into the shared volume on every start
RUN cp -a /var/www/html/public /var/www/html/public-snapshot

# Copy PHP config
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini

# Copy and set up entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Set correct permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
