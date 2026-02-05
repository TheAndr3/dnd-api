# =============================================================================
# Stage 1: Composer Dependencies
# =============================================================================
FROM composer:2 AS composer

WORKDIR /app

# Copy composer files first for better layer caching
COPY composer.json composer.lock ./

# Install production dependencies only
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --no-autoloader \
    --prefer-dist

# Copy the rest of the application
COPY . .

# Generate optimized autoloader
RUN composer dump-autoload --optimize --no-dev

# =============================================================================
# Stage 2: Production Image
# =============================================================================
FROM php:8.2-fpm-alpine

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    nginx \
    supervisor \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install \
    pdo_pgsql \
    pgsql \
    opcache \
    zip \
    bcmath \
    && rm -rf /var/cache/apk/*

# Configure PHP for production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Configure OPcache for production
RUN echo "opcache.enable=1" >> "$PHP_INI_DIR/conf.d/opcache.ini" \
    && echo "opcache.memory_consumption=128" >> "$PHP_INI_DIR/conf.d/opcache.ini" \
    && echo "opcache.interned_strings_buffer=8" >> "$PHP_INI_DIR/conf.d/opcache.ini" \
    && echo "opcache.max_accelerated_files=10000" >> "$PHP_INI_DIR/conf.d/opcache.ini" \
    && echo "opcache.validate_timestamps=0" >> "$PHP_INI_DIR/conf.d/opcache.ini"

# Set working directory
WORKDIR /var/www/html

# Copy application from composer stage
COPY --from=composer /app /var/www/html

# Copy Docker configuration files
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/start.sh /usr/local/bin/start.sh

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod +x /usr/local/bin/start.sh

# Generate Swagger documentation during build
RUN php artisan l5-swagger:generate

# Expose port 80
EXPOSE 80

# Start services via script
CMD ["/usr/local/bin/start.sh"]
