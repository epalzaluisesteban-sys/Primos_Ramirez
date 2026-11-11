#!/bin/sh
set -e
cd /var/www/html

# Render indica el puerto en $PORT (por defecto 10000)
PORT="${PORT:-10000}"
sed -ri "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Esperar a que la base de datos acepte conexiones y crear/actualizar las tablas
php artisan migrate --force

# Primer usuario Dueño (solo si no existe ninguno y se definió ADMIN_PASSWORD)
php artisan db:seed --class=AdminInicialSeeder --force

# Tasa del BCV inicial (si falla por falta de internet, no importa: se reintenta al abrir el sistema)
php artisan tasa:actualizar || true

php artisan config:cache
php artisan route:cache
php artisan view:cache

chown -R www-data:www-data storage bootstrap/cache
exec apache2-foreground
