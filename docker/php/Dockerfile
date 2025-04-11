FROM php:8.2-fpm-alpine

# Install minimal system dependencies
RUN apk add --no-cache \
    libpng \
    libjpeg-turbo \
    freetype \
    libxml2

# Install PHP extensions using the docker-php-ext-install script
RUN apk add --no-cache --virtual .build-deps \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libxml2-dev \
    oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql mbstring exif pcntl bcmath gd \
    && apk del .build-deps

# Set working directory
WORKDIR /var/www/html

# Copy existing application directory
COPY . /var/www/html

# Change ownership to www-data and set permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html/

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]