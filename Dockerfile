FROM dunglas/frankenphp:1-php8.4

# Install system dependencies dan PHP extensions
RUN install-php-extensions \
    pdo_pgsql \
    pgsql \
    redis \
    opcache \
    zip \
    pcntl \
    bcmath \
    intl \
    exif \
    gd \
    && apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    postgresql-client \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy ALL application files first
COPY . .

# Create directories SEBELUM composer install
RUN mkdir -p \
    bootstrap/cache \
    storage/app/public \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

# Install dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-plugins

# Set permissions SETELAH composer install
RUN chown -R www-data:www-data /app \
    && chmod -R 775 bootstrap/cache storage

# Configure PHP for production
RUN cat > /usr/local/etc/php/conf.d/laravel.ini <<'EOF'
; Laravel optimizations
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0

; Memory and execution
memory_limit=512M
max_execution_time=300
upload_max_filesize=100M
post_max_size=100M

; Session
session.gc_maxlifetime=7200

; Realpath cache
realpath_cache_size=4096K
realpath_cache_ttl=600
EOF

# Expose ports
EXPOSE 80 443

# Default command
CMD ["frankenphp", "run"]
