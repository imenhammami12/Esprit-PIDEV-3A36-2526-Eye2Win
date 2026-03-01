#!/bin/bash

# Cache Symfony en production
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

# Migrations (si tu as une DB)
php bin/console doctrine:migrations:migrate --no-interaction

# Démarrer PHP-FPM et Nginx
php-fpm -D
nginx -g "daemon off;"

