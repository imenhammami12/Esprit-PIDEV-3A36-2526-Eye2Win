#!/bin/bash
set -e

# Force production mode so dev bundles (DebugBundle, WebProfilerBundle) are not loaded.
# Render env vars can still override these if set.
export APP_ENV=prod
export APP_DEBUG=0

# Cache Symfony en production
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

# Migrations (si tu as une DB)
php bin/console doctrine:migrations:migrate --no-interaction

# Démarrer PHP-FPM et Nginx
php-fpm -D
nginx -g "daemon off;"