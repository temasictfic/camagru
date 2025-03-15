FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libgd-dev

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Install PHPMailer
RUN composer require phpmailer/phpmailer

# Copy existing application directory
COPY . /var/www/html

# Change ownership to www-data and set permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html/

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]