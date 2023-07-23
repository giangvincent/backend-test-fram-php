# Use the official PHP image
FROM php:8.1-fpm

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    nginx \
    libpng-dev \
    && docker-php-ext-install pdo pdo_mysql gd

# Copy Nginx configuration
COPY nginx.conf /etc/nginx/sites-available/default

# Set working directory
WORKDIR /var/www/html

# Copy the PHP application files to the container
COPY . /var/www/html

# Expose port 80 to access the web server
EXPOSE 80

# Start Nginx and PHP-FPM
CMD service nginx start && php-fpm
