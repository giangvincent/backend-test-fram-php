# Use the PHP 8.1 image for Apple Silicon (ARM64)
FROM php:8.1-fpm

# Install additional PHP extensions if needed
# For example, if you need pdo_mysql extension:
RUN docker-php-ext-install pdo_mysql

# Set the working directory in the container
WORKDIR /var/www/html

# Copy your PHP application files to the container
COPY . /var/www/html

# Install and configure Nginx
RUN apt-get update && apt-get install -y nginx
COPY nginx/default /etc/nginx/sites-available/default

# Expose port 8000
EXPOSE 8000

# Start the PHP-FPM and Nginx services
CMD service php8.1-fpm start && nginx -g 'daemon off;'
