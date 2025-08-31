# ESBTP Application Deployment Guide

This document provides step-by-step instructions for deploying the ESBTP application to a production environment using Apache.

## Prerequisites

-   Web server (Apache 2.4+)
-   PHP 8.0+ with required extensions
-   MySQL 5.7+ or MariaDB 10.3+
-   Composer

## Deployment Steps

### 1. Clone the Repository

```bash
git clone <repository-url>
cd ESBTP-yAKRO
```

### 2. Install Dependencies

```bash
composer install --no-dev --optimize-autoloader
```

### 3. Environment Configuration

Create a `.env` file with production settings:

```bash
cp .env.example .env
```

Update the following values in the `.env` file:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=http://your-domain.com

DB_CONNECTION=mysql
DB_HOST=your-database-host
DB_PORT=3306
DB_DATABASE=your-database-name
DB_USERNAME=your-database-username
DB_PASSWORD=your-database-password
```

Generate an application key:

```bash
php artisan key:generate
```

### 4. Database Setup

Create the database on your production server and run the migrations:

```bash
php artisan migrate
```

### 5. Optimize for Production

```bash
php artisan config:cache
php artisan view:cache
php artisan optimize
```

Note: If you encounter route caching errors, skip that step. It means you have duplicate route names that need to be fixed first.

### 6. File Permissions

Make sure these directories are writable by the web server:

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

Or on Windows:

```
icacls storage\* /grant "everyone:(OI)(CI)F" /T
icacls bootstrap\cache\* /grant "everyone:(OI)(CI)F" /T
```

### 7. Apache Configuration

Create a virtual host configuration file in your Apache configuration directory (usually in `/etc/apache2/sites-available/` on Linux or `C:/xampp/apache/conf/extra/httpd-vhosts.conf` on Windows with XAMPP):

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    ServerAdmin webmaster@your-domain.com
    DocumentRoot "/path/to/ESBTP-yAKRO/public"

    <Directory "/path/to/ESBTP-yAKRO/public">
        Options Indexes FollowSymLinks MultiViews
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/esbtp-error.log
    CustomLog ${APACHE_LOG_DIR}/esbtp-access.log combined
</VirtualHost>
```

Replace `your-domain.com` with your actual domain name and `/path/to/ESBTP-yAKRO` with the actual path to your application.

### 8. Enable the Site (Linux)

```bash
sudo a2ensite your-site-config.conf
sudo systemctl restart apache2
```

### 9. Update Hosts File (for Testing)

Add this line to your hosts file (`/etc/hosts` on Linux/Mac, `C:\Windows\System32\drivers\etc\hosts` on Windows):

```
127.0.0.1 your-domain.com
```

### 10. SSL Configuration (Optional but Recommended)

For a secure setup, consider adding SSL:

```bash
sudo certbot --apache -d your-domain.com
```

Or configure manually in your VirtualHost.

## Troubleshooting

### Permission Issues

If you encounter permission issues:

```bash
sudo chown -R www-data:www-data /path/to/ESBTP-yAKRO
sudo find /path/to/ESBTP-yAKRO -type f -exec chmod 644 {} \;
sudo find /path/to/ESBTP-yAKRO -type d -exec chmod 755 {} \;
sudo chmod -R 775 /path/to/ESBTP-yAKRO/storage /path/to/ESBTP-yAKRO/bootstrap/cache
```

### Blank Page or 500 Error

Check the Apache error logs:

```bash
tail -f /var/log/apache2/error.log
```

Or on Windows:

```
type C:\xampp\apache\logs\error.log
```

### Enable Error Display Temporarily

If you need to debug, you can temporarily enable error display in `.env`:

```
APP_DEBUG=true
```

Don't forget to set it back to `false` after debugging.

## Post-Deployment Checklist

-   [ ] Application loads correctly
-   [ ] User authentication works
-   [ ] All features function properly
-   [ ] Database connections are secure
-   [ ] Error logs are being written correctly
-   [ ] Application has correct permissions
-   [ ] Debug mode is turned off
-   [ ] Cache is enabled
-   [ ] SSL is properly configured

## Important Production Settings

For optimal performance, make these adjustments in `php.ini`:

```ini
; Maximum memory for a script
memory_limit = 256M

; Maximum upload file size
upload_max_filesize = 20M
post_max_size = 20M

; Maximum execution time
max_execution_time = 120

; Enable OPcache for better performance
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=60
opcache.fast_shutdown=1
opcache.enable_cli=1
```

For better security, also set:

```ini
expose_php = Off
display_errors = Off
log_errors = On
error_log = /path/to/php_errors.log
```

## Regular Maintenance

-   Run `composer update` periodically to keep dependencies updated
-   Check logs for any errors
-   Backup the database regularly
-   Monitor server resources
-   Keep the operating system and software updated
