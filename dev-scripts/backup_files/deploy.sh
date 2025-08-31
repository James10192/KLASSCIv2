#!/bin/bash

echo "==================================================="
echo "ESBTP Application Deployment Script"
echo "==================================================="
echo ""

# Set environment variables
PROD_ENV_FILE=.env.production
ENVIRONMENT=production
BACKUP_DIR="backups/$(date +%Y%m%d)"

echo "Step 1: Creating backup directory..."
mkdir -p "$BACKUP_DIR"

echo "Step 2: Backing up current files..."
cp -R public "$BACKUP_DIR/public"
cp .env "$BACKUP_DIR/.env.backup"
echo "Database backup..."
php artisan db:backup --database=mysql --destination=local --compression=gzip --destination-path="$BACKUP_DIR"

echo "Step 3: Installing production dependencies..."
composer install --no-dev --optimize-autoloader

echo "Step 4: Copying production environment file..."
cp "$PROD_ENV_FILE" .env

echo "Step 5: Clearing cache and optimizing application..."
php artisan cache:clear
php artisan config:cache
php artisan view:cache
php artisan optimize

echo "Step 6: Setting correct permissions..."
chmod -R 775 storage bootstrap/cache
find storage -type d -exec chmod 775 {} \;
find storage -type f -exec chmod 664 {} \;
find bootstrap/cache -type d -exec chmod 775 {} \;
find bootstrap/cache -type f -exec chmod 664 {} \;

# If using Apache with www-data user
if command -v apache2 >/dev/null 2>&1; then
    echo "Setting Apache-specific permissions..."
    chown -R www-data:www-data storage bootstrap/cache
fi

echo "==================================================="
echo "Deployment preparation completed!"
echo "==================================================="
echo ""
echo "Next steps for production:"
echo "1. Copy the entire project to the production server"
echo "2. Configure the Apache virtual host using apache_vhost.conf"
echo "3. Restart Apache with: sudo systemctl restart apache2"
echo "4. Visit the site at http://esbtp.nnagroup.net to verify"
echo ""
echo "FTP Connection Details:"
echo "Host: ftp.nnagroup.net"
echo "Username: Marcel@nnagroup.net"
echo "Port: 21"
echo ""
