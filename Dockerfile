# Stage 1: Build Assets
FROM node:20-alpine AS asset-builder
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build

# Stage 2: Production FrankenPHP Server
FROM dunglas/frankenphp:1.4-php8.3 AS runner

# Install system dependencies & PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    && rm -rf /var/lib/apt/lists/*

RUN install-php-extensions \
    pdo_pgsql \
    redis \
    opcache \
    pcntl \
    intl \
    zip \
    gd \
    apcu

# Raise PHP upload limits for note image uploads
COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/zz-uploads.ini

# Install Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy application files
COPY . .
# Copy compiled assets from Stage 1
COPY --from=asset-builder /app/public/build ./public/build

# Run composer installation
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Set permissions for Laravel
RUN chown -R www-data:www-data storage bootstrap/cache

# Copy custom entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
