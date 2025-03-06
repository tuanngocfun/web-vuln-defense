#!/bin/bash
set -e

# Ensure resources/views-cache directory exists with proper permissions
mkdir -p /var/www/html/resources/views-cache
chown -R www-data:www-data /var/www/html/resources
chmod -R 775 /var/www/html/resources

# Run migrations and seeding automatically
if [ -f "/var/www/docker/migration.sh" ]; then
  echo "Running migrations and seeding..."
  bash /var/www/docker/migration.sh
fi

# Execute the original command
exec "$@"