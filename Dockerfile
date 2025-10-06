# Use official PHP image with Apache
FROM php:8.2-apache

# Set working directory
WORKDIR /var/www/html

# Install dependencies and Composer safely
RUN apt-get update && \
    apt-get install -y --no-install-recommends git unzip curl && \
    rm -rf /var/lib/apt/lists/* && \
    curl -sS https://getcomposer.org/installer | php && \
    mv composer.phar /usr/local/bin/composer

# Enable Apache modules
RUN a2enmod rewrite

# Copy project files
COPY . /var/www/html/

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Install Composer dependencies (ignore platform warnings)
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs || true

# Expose port 80 for Render
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
