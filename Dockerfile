FROM php:8.3-fpm

# Installation des dépendances système
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev \
    zip unzip libzip-dev nginx \
    && rm -rf /var/lib/apt/lists/*

# Extensions PHP (ajout de zip !)
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# So PHP-FPM workers run in prod (no DebugBundle / WebProfilerBundle in container)
RUN echo 'env[APP_ENV] = prod' >> /usr/local/etc/php-fpm.d/www.conf \
    && echo 'env[APP_DEBUG] = 0' >> /usr/local/etc/php-fpm.d/www.conf

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

# Skip scripts during install so cache:clear doesn't need .env (not available at build time).
# cache:clear runs at container start in start.sh when Render env vars are available.
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Minimal .env so Symfony's Dotenv finds a file at runtime; real values come from Render env vars.
RUN echo 'APP_ENV=prod' > /var/www/.env

# Create var dirs (gitignored, so not in image) then set permissions
RUN mkdir -p /var/www/var/cache /var/www/var/log \
    && chown -R www-data:www-data /var/www/var /var/www/public

COPY docker/nginx.conf /etc/nginx/sites-available/default

COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80

CMD ["/start.sh"]