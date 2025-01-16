#!/bin/bash

echo "Deployment started ..."

# Set the working directory to ensure artisan commands run correctly
cd /home/staging/public_html

# Install dependencies based on lock file

composer install --ignore-platform-reqs

# Clear the old cache

php artisan clear-compiled
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan migrate --force

# Exit maintenance mode
php artisan up

echo "Application deployed!"