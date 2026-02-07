FROM php:8.1-apache

WORKDIR /var/www/html

# Install dependencies
RUN apt-get update && apt-get install -y \
    curl git zip unzip \
    libssl-dev libcurl4-openssl-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql curl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader 2>/dev/null || true

# Set permissions
RUN chmod -R 755 /var/www/html

# Expose port
EXPOSE 8080

# Start server
CMD ["php", "-S", "0.0.0.0:8080", "-t", "/var/www/html"]
