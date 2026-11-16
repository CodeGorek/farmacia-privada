#!/usr/bin/env bash
# Guía Paso 7: Despliegue en AWS (Ubuntu 24.04 + Nginx + PHP 8.3 + MySQL RDS + MongoDB)
set -euo pipefail

REPO_URL="${1:-https://github.com/tu-usuario/farmacia-privada.git}"
APP_DIR="/var/www/farmacia-privada"

cd /var/www
if [ ! -d "$APP_DIR" ]; then
    sudo git clone "$REPO_URL" farmacia-privada
fi

cd "$APP_DIR"

# Permisos de servidor web
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Instalación de dependencias de producción
composer install --optimize-autoloader --no-dev

if [ ! -f .env ]; then
    cp .env.example .env
    echo "Configura las credenciales de AWS RDS y MongoDB en $APP_DIR/.env antes de continuar."
fi

# Generar clave de aplicación y migrar bases de datos
php artisan key:generate --force
php artisan migrate --force
