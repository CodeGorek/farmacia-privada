param(
    [Parameter(Mandatory=$true)]
    [string]$IpPublica,

    [Parameter(Mandatory=$false)]
    [string]$RutaPem = "$env:USERPROFILE\Downloads\farmacia-key.pem"
)

$ErrorActionPreference = "Stop"
$ProjectDir = Resolve-Path "$PSScriptRoot\.."
$TarFile = "$env:TEMP\farmacia-privada-deploy.tar.gz"

Write-Host "1. Empaquetando proyecto Laravel 11 desde $ProjectDir..." -ForegroundColor Cyan
Set-Location $ProjectDir
tar -czf $TarFile --exclude="vendor" --exclude="node_modules" --exclude=".git" --exclude="database/database.sqlite" .

Write-Host "2. Subiendo paquete a AWS EC2 (ubuntu@$IpPublica)..." -ForegroundColor Cyan
scp -i "$RutaPem" -o StrictHostKeyChecking=no "$TarFile" "ubuntu@${IpPublica}:/tmp/farmacia-privada.tar.gz"

Write-Host "3. Configurando servidor, instalando dependencias y migrando base de datos en AWS..." -ForegroundColor Cyan
$RemoteScript = @'
set -e
sudo mkdir -p /var/www/farmacia-privada
sudo tar -xzf /tmp/farmacia-privada.tar.gz -C /var/www/farmacia-privada
cd /var/www/farmacia-privada

sudo chown -R ubuntu:www-data /var/www/farmacia-privada
composer config audit.block-insecure false
composer install --optimize-autoloader --no-dev --ignore-platform-req=ext-mongodb --no-interaction

cp .env.example .env
touch database/database.sqlite
sed -i 's/DB_CONNECTION=mysql/DB_CONNECTION=sqlite/g' .env
sed -i 's/DB_DATABASE=farmacia/# DB_DATABASE=farmacia/g' .env

php artisan key:generate --force
php artisan migrate:fresh --seed --force
php artisan config:clear
php artisan view:clear

sudo chown -R www-data:www-data /var/www/farmacia-privada/storage /var/www/farmacia-privada/bootstrap/cache /var/www/farmacia-privada/database
sudo chmod -R 775 /var/www/farmacia-privada/storage /var/www/farmacia-privada/bootstrap/cache /var/www/farmacia-privada/database
sudo systemctl restart php8.3-fpm nginx
echo "DESPLIEGUE COMPLETADO EN AWS!"
'@

ssh -i "$RutaPem" -o StrictHostKeyChecking=no "ubuntu@${IpPublica}" $RemoteScript

Write-Host "¡Listo! Tu sistema ya está publicado en: http://$IpPublica" -ForegroundColor Green
