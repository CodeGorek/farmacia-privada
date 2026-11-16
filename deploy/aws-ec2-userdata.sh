#!/bin/bash
# ==============================================================================
# AWS EC2 User-Data Script (Ubuntu 24.04 LTS) — Sistema Farmacia Privada
# Pega este script en EC2 -> Launch Instance -> Advanced Details -> User Data
# Instala automáticamente: Nginx, PHP 8.3, MySQL, MongoDB, Composer y Laravel 11
# ==============================================================================
set -euxo pipefail
export DEBIAN_FRONTEND=noninteractive

# 1. Actualizar sistema e instalar paquetes base
apt-get update -y
apt-get install -y nginx mysql-server curl git unzip software-properties-common gnupg ca-certificates

# 2. Instalar PHP 8.3 y extensiones requeridas por Laravel 11
apt-get install -y php8.3-fpm php8.3-cli php8.3-mysql php8.3-sqlite3 php8.3-xml php8.3-mbstring php8.3-curl php8.3-zip php8.3-bcmath php8.3-intl php8.3-mongodb

# 3. Instalar Composer globalmente
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# 4. Configurar base de datos MySQL local 'farmacia'
systemctl enable --now mysql
mysql -e "CREATE DATABASE IF NOT EXISTS farmacia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS 'farmacia_user'@'localhost' IDENTIFIED BY 'Farmacia2026!';"
mysql -e "GRANT ALL PRIVILEGES ON farmacia.* TO 'farmacia_user'@'localhost'; FLUSH PRIVILEGES;"

# 5. Configurar VirtualHost de Nginx apuntando a /var/www/farmacia-privada/public
cat > /etc/nginx/sites-available/farmacia-privada << 'EOF'
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    server_name _;
    root /var/www/farmacia-privada/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

rm -f /etc/nginx/sites-enabled/default
ln -sf /etc/nginx/sites-available/farmacia-privada /etc/nginx/sites-enabled/farmacia-privada
systemctl enable --now php8.3-fpm
systemctl restart nginx
