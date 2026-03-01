#!/bin/bash
set -e

# Force production mode so dev bundles (DebugBundle, WebProfilerBundle) are not loaded.
# Render env vars can still override these if set.
export APP_ENV=prod
export APP_DEBUG=0

# Cache Symfony en production
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

# Ensure www-data can write to cache (cache:clear runs as root, PHP-FPM runs as www-data)
chown -R www-data:www-data /var/www/var

# Migrations: run only if DATABASE_URL is set; do not fail deploy if DB is unreachable
if [ -n "${DATABASE_URL}" ]; then
  php bin/console doctrine:migrations:migrate --no-interaction || true
fi

# Démarrer PHP-FPM et Nginx
php-fpm -D
nginx -g "daemon off;"