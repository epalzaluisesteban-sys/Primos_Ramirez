#!/bin/sh
set -e
cd /var/www/html

# Puerto: el que declara el Dockerfile (EXPOSE 80), salvo que el servicio indique otro en $PORT
PORT="${PORT:-80}"
sed -ri "s/Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Procesos de Apache: cada uno ocupa entre 30 y 50 MB. En planes de 256 MB (Back4app) se baja con APACHE_MAX_WORKERS
cat > /etc/apache2/mods-available/mpm_prefork.conf <<EOF
<IfModule mpm_prefork_module>
    StartServers 2
    MinSpareServers 1
    MaxSpareServers 3
    MaxRequestWorkers ${APACHE_MAX_WORKERS:-10}
    MaxConnectionsPerChild 500
</IfModule>
EOF

# Memoria máxima por página (php.ini trae 384M). Debe quedar por debajo de la memoria del plan
if [ -n "$PHP_MEMORY_LIMIT" ]; then
    echo "memory_limit = ${PHP_MEMORY_LIMIT}" > /usr/local/etc/php/conf.d/zz-memoria.ini
fi

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
