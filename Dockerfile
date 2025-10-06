# Use the official PHP image
FROM php:8.2-apache

# Install necessary PHP extensions and tools
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache mod_rewrite (important for clean URLs)
RUN a2enmod rewrite

# Copy project files into the container
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html/

# Install Composer and dependencies
RUN apt-get update && apt-get install -y git unzip \
    && curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer \
    && composer install --no-dev --optimize-autoloader

# Expose port 80 for Render
EXPOSE 80
