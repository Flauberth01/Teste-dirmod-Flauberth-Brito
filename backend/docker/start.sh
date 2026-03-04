#!/bin/sh
set -e

cd /var/www/html

if [ ! -f .env ]; then
  cp .env.docker .env
fi

# Ensure DB values in .env match container runtime variables.
sed -i "s/^DB_CONNECTION=.*/DB_CONNECTION=${DB_CONNECTION}/" .env
sed -i "s/^DB_HOST=.*/DB_HOST=${DB_HOST}/" .env
sed -i "s/^DB_PORT=.*/DB_PORT=${DB_PORT}/" .env
sed -i "s/^DB_DATABASE=.*/DB_DATABASE=${DB_DATABASE}/" .env
sed -i "s/^DB_USERNAME=.*/DB_USERNAME=${DB_USERNAME}/" .env
sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=${DB_PASSWORD}/" .env

if [ ! -f vendor/autoload.php ]; then
  composer install --no-interaction --prefer-dist
fi

if ! grep -q '^APP_KEY=base64:' .env; then
  php artisan key:generate --force
fi

echo "Aguardando PostgreSQL..."
until php -r "try { new PDO('pgsql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD')); } catch (\Throwable \$e) { exit(1); }"; do
  sleep 2
done

php artisan config:clear
php artisan migrate --force

php artisan serve --host=0.0.0.0 --port=8000
