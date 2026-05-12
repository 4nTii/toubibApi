FROM php:8.4-fpm

# Install system dependencies (Debian-based)
RUN apt-get update && apt-get install -y \
    bash \
    curl \
    git \
    unzip \
    openssl \
    libicu-dev \
    libonig-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        intl \
        zip \
        mbstring \
        gd \
        opcache \
        sockets

# Install APCu
RUN pecl install apcu && docker-php-ext-enable apcu

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy PHP config
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/zz-custom.conf

# Copy application source
COPY . .

# Install Composer dependencies (production)
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Generate JWT keys if missing
RUN mkdir -p config/jwt \
    && if [ ! -f config/jwt/private.pem ]; then \
        php bin/console lexik:jwt:generate-keypair; \
    fi

# Set correct permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/var

USER www-data

EXPOSE 9000

CMD ["php-fpm"]