#!/bin/sh
set -e

# Default to 10000 (Render default port) if PORT is not set
PORT="${PORT:-10000}"
sed -i "s/LISTEN_PORT/${PORT}/g" /etc/nginx/sites-available/default

# Generate APP_KEY if missing
if [ -z "$APP_KEY" ]; then
    echo "Generating Application Key..."
    php artisan key:generate --force
fi

# Ensure storage directories exist and are writable
mkdir -p /var/www/html/storage/app/public/labels
mkdir -p /var/www/html/storage/app/public/whatsapp_images
mkdir -p /var/www/html/storage/framework/cache/data
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/logs

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Create public storage symlink
php artisan storage:link || true

# Initialize database if using SQLite and file does not exist
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    if [ ! -f "/var/www/html/database/database.sqlite" ]; then
        touch /var/www/html/database/database.sqlite
        chown www-data:www-data /var/www/html/database/database.sqlite
        chmod 664 /var/www/html/database/database.sqlite
    fi
fi

# Run migrations and initial seeder
echo "Running Database Migrations..."
php artisan migrate --force || true

echo "Seeding default admin user..."
php artisan db:seed --force || true

# Optimize cache for production
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

echo "Starting Nginx and PHP-FPM on port ${PORT}..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
