#!/bin/bash

# Load project name from .env
if [ -f .env ]; then
    export $(cat .env | grep -v '#' | xargs)
fi

CONTAINER="${PROJECT_NAME}_php"

echo "Setting up Laravel in $CONTAINER..."

# Create Laravel project (installs to /var/www)
docker exec -it $CONTAINER composer create-project laravel/laravel temp --prefer-dist

# Move files from temp to root (because /var/www is mounted)
docker exec -it $CONTAINER sh -c "mv temp/* temp/.* . 2>/dev/null; rmdir temp"

# Set permissions
docker exec -it $CONTAINER chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Generate app key
docker exec -it $CONTAINER php artisan key:generate

# Update .env database config
docker exec -it $CONTAINER sed -i "s/DB_HOST=.*/DB_HOST=mysql/" .env
docker exec -it $CONTAINER sed -i "s/DB_DATABASE=.*/DB_DATABASE=${PROJECT_NAME}/" .env
docker exec -it $CONTAINER sed -i "s/DB_USERNAME=.*/DB_USERNAME=root/" .env
docker exec -it $CONTAINER sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=password/" .env

# Run migrations
docker exec -it $CONTAINER php artisan migrate

echo "Done. Visit http://localhost:${WEB_PORT}"