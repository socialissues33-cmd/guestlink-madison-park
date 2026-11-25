# GuestLink – Madison Park Hotel (Production Laravel 12 + SQLite WAL Container)
# Ready for Render.com, Railway.app, Fly.io, or any Docker cloud host.
FROM php:8.3-apache

# Install system dependencies & PHP extensions required by Laravel 12 + SimpleQrCode + SQLite
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        sqlite3 \
        libsqlite3-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libzip-dev \
        libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo \
        pdo_sqlite \
        gd \
        bcmath \
        zip \
        mbstring \
        opcache \
    && a2enmod rewrite deflate headers expires \
    && rm -rf /var/lib/apt/lists/*

# Tune PHP & OPcache for smooth multi-guest presentation performance
RUN { \
        echo "memory_limit=256M"; \
        echo "upload_max_filesize=12M"; \
        echo "post_max_size=16M"; \
        echo "max_execution_time=60"; \
        echo "opcache.enable=1"; \
        echo "opcache.memory_consumption=128"; \
        echo "opcache.interned_strings_buffer=16"; \
        echo "opcache.max_accelerated_files=10000"; \
        echo "opcache.validate_timestamps=0"; \
    } > /usr/local/etc/php/conf.d/guestlink-prod.ini

# Configure Apache DocumentRoot -> /var/www/html/public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && { \
        echo '<Directory /var/www/html/public>'; \
        echo '    Options -Indexes +FollowSymLinks'; \
        echo '    AllowOverride All'; \
        echo '    Require all granted'; \
        echo '    <FilesMatch "\.(jpg|jpeg|png|webp|gif|svg|css|js|ico|woff2)$">'; \
        echo '        Header set Cache-Control "public, max-age=604800"'; \
        echo '    </FilesMatch>'; \
        echo '</Directory>'; \
        echo '<IfModule mod_deflate.c>'; \
        echo '    AddOutputFilterByType DEFLATE text/html text/plain text/css application/javascript text/javascript application/json image/svg+xml'; \
        echo '</IfModule>'; \
    } > /etc/apache2/conf-available/guestlink.conf \
    && a2enconf guestlink

WORKDIR /var/www/html

# Install Composer dependencies first for faster Docker layer caching
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Copy application source (includes public/images and bundled database/database.sqlite)
COPY . .

# Run package discovery and ensure writable runtime directories
RUN mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
        public/uploads/maintenance \
        public/uploads/menu \
    && rm -f bootstrap/cache/*.php storage/tunnel.log \
    && composer dump-autoload --optimize --no-dev \
    && chown -R www-data:www-data storage bootstrap/cache database public/uploads \
    && chmod -R 775 storage bootstrap/cache database public/uploads

# Install production entrypoint (strip CRLF in case built from Windows)
COPY docker/entrypoint.sh /usr/local/bin/guestlink-entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/guestlink-entrypoint.sh \
    && chmod +x /usr/local/bin/guestlink-entrypoint.sh

ENV PORT=8080
EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=15s --retries=3 \
    CMD php -r "exit(@file_get_contents('http://127.0.0.1:' . (getenv('PORT') ?: '8080') . '/up') !== false ? 0 : 1);"

ENTRYPOINT ["/usr/local/bin/guestlink-entrypoint.sh"]
