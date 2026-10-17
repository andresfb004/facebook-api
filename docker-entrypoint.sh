#!/bin/sh
set -e

echo "=== Limpiando y optimizando cachés de Laravel ==="
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "=== Ejecutando migraciones de base de datos en PostgreSQL ==="
php artisan migrate --force

echo "=== Creando enlace simbólico del storage público ==="
php artisan storage:link || true

echo "=== Iniciando servidor PHP integrado en puerto ${PORT:-10000} ==="
exec php artisan serve --host=0.0.0.0 --port=${PORT:-10000}