#!/bin/bash
# Render Build Script

echo "-----> Building PHP Menu Scanner"

# Install Composer if not available
if ! command -v composer &> /dev/null; then
    echo "Installing Composer..."
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
fi

# Install PHP dependencies
if [ -f "composer.json" ]; then
    echo "Installing Composer dependencies..."
    composer install --no-dev --optimize-autoloader
fi

# Create uploads directory if it doesn't exist
mkdir -p uploads
chmod 755 uploads

# Create public/qr directory
mkdir -p public/qr
chmod 755 public/qr

echo "-----> Build complete"
