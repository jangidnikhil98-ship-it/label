#!/bin/sh
set -e

# Default to 10000 (Render default port) if PORT is not set
PORT="${PORT:-10000}"
sed -i "s/LISTEN_PORT/${PORT}/g" /etc/nginx/sites-available/default

# Ensure .env file exists so artisan commands (like key:generate) succeed
if [ ! -f "/var/www/html/.env" ]; then
    if [ -f "/var/www/html/.env.example" ]; then
        echo "Creating .env from .env.example..."
        cp /var/www/html/.env.example /var/www/html/.env
    else
        echo "Creating blank .env..."
        touch /var/www/html/.env
    fi
    chown www-data:www-data /var/www/html/.env
    chmod 664 /var/www/html/.env
fi

# Generate APP_KEY if missing or empty
if [ -z "$APP_KEY" ]; then
    echo "Generating Application Key..."
    php artisan key:generate --force || true
fi

# Ensure storage directories exist and are writable
mkdir -p /var/www/html/storage/app/public/labels
mkdir -p /var/www/html/storage/app/public/whatsapp_images
mkdir -p /var/www/html/storage/framework/cache/data
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/database

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database

# Create public storage symlink
php artisan storage:link || true

# Initialize database if using SQLite and file does not exist
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    if [ ! -f "/var/www/html/database/database.sqlite" ]; then
        echo "Creating database.sqlite file..."
        touch /var/www/html/database/database.sqlite
    fi
    chown www-data:www-data /var/www/html/database/database.sqlite
    chmod 664 /var/www/html/database/database.sqlite
fi

# Run migrations and initial seeder
echo "Running Database Migrations..."
php artisan migrate --force || true

echo "Seeding default admin user..."
php artisan db:seed --force || true

# Clear and refresh production caches
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

echo "Starting Nginx and PHP-FPM on port ${PORT}..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
