# Use an official PHP image
FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y git unzip curl

# Enable commonly used PHP extensions
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Set working directory
WORKDIR /var/www/html

# Copy your project files into the container
COPY . /var/www/html/

# Install Composer and PHP dependencies
RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer \
    && composer install --no-dev --optimize-autoloader --ignore-platform-reqs || true

# Expose the web port
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
