FROM php:8.2-cli

# Install system dependencies & SQLite
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    sqlite3 \
    libsqlite3-dev \
    libpq-dev

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_sqlite pdo_pgsql pgsql mbstring bcmath

# Configure PHP upload limits & execution memory for large Drive uploads
RUN echo "upload_max_filesize = 4096M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 4096M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 2048M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time = 1800" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_input_time = 1800" >> /usr/local/etc/php/conf.d/uploads.ini

# Get Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set up permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache database

# Expose port
EXPOSE 8000

# Start Laravel (ensure permissions, purge stale view caches, run migrations & idempotent seed, start server)
CMD sh -c "mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache storage/logs && chmod -R 777 storage bootstrap/cache database && touch database/database.sqlite && chmod 666 database/database.sqlite && php artisan optimize:clear && php artisan view:clear && php artisan migrate --force && php artisan db:seed --force && php -d upload_max_filesize=4096M -d post_max_size=4096M -d memory_limit=2048M artisan serve --host 0.0.0.0 --port ${PORT:-8000}"
