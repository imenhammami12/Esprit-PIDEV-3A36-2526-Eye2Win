#!/bin/bash
set -e

export APP_ENV=prod
export APP_DEBUG=0

# Nettoyer complètement le cache
rm -rf /var/www/var/cache/*

# Clear et warmup au démarrage (pas au build)
php bin/console cache:clear --env=prod --no-warmup
php bin/console cache:warmup --env=prod

chown -R www-data:www-data /var/www/var

if [ -n "${DATABASE_URL}" ]; then
  php bin/console doctrine:migrations:migrate --no-interaction || true
fi

ln -sf /dev/stdout /var/www/var/log/prod.log

php-fpm -D
nginx -g "daemon off;"