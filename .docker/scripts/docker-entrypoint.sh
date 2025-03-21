#!/bin/bash
set -e

# Fix permission after volume mount
echo "Fixing permissions for views-cache..."
mkdir -p /var/www/html/resources/views-cache
chown -R www-data:www-data /var/www/html/resources/views-cache
chmod -R 775 /var/www/html/resources/views-cache

# Run migrations and seeding automatically
if [ -f "/var/www/docker/migration.sh" ]; then
  echo "Running migrations and seeding..."
  bash /var/www/docker/migration.sh
fi

# Start Apache
exec "$@"
