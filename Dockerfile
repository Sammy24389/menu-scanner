# PHP Menu Scanner - Dockerfile for Railway
FROM php:8.2-cli

# Set working directory
WORKDIR /app

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Create directories
RUN mkdir -p data uploads public/qr && \
    chmod -R 755 data uploads public/qr

# Expose port
EXPOSE $PORT

# Start PHP built-in server
CMD php -S 0.0.0.0:$PORT -t public
