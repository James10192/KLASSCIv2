# ESBTP Production Deployment Guide

This document provides detailed instructions for deploying the ESBTP application to a production environment.

## Prerequisites

-   Web server (Apache 2.4+)
-   PHP 8.0+ with required extensions
-   MySQL 5.7+ or MariaDB 10.3+
-   Composer

## Files Prepared for Production

We've prepared the following files specifically for production deployment:

1. `.env.production` - Environment configuration for production
2. `apache_vhost.conf` - Apache virtual host configuration
3. `public/.htaccess.production` - Enhanced .htaccess file for production
4. `deploy.bat` - Windows deployment script
5. `deploy.sh` - Linux/Unix deployment script

## Deployment Steps

### 1. Run the Deployment Script

#### On Windows:

```
deploy.bat
```

#### On Linux/Unix:

```
chmod +x deploy.sh
./deploy.sh
```

The script will:

-   Create a backup of your current files
-   Install production dependencies
-   Copy the production environment file
-   Clear caches and optimize the application
-   Set correct file permissions

### 2. Configure Apache Virtual Host

Copy the `apache_vhost.conf` file to your Apache configuration directory:

#### On Windows (XAMPP):

Copy to `C:/xampp/apache/conf/extra/httpd-vhosts.conf` or include it in your Apache configuration.

#### On Linux:

```bash
sudo cp apache_vhost.conf /etc/apache2/sites-available/esbtp.conf
sudo a2ensite esbtp.conf
sudo systemctl reload apache2
```

### 3. Update .htaccess File

Copy the production .htaccess file to the public directory:

```
cp public/.htaccess.production public/.htaccess
```

### 4. Transfer Files to Production Server

#### Using FTP:

Use your FTP client to connect with the following credentials:

-   Host: ftp.nnagroup.net
-   Username: Marcel@nnagroup.net
-   Password: Password1!
-   Port: 21

Transfer all application files to the target directory on your server.

#### Using SCP (if available):

```bash
scp -r /path/to/local/ESBTP-yAKRO user@server:/path/to/destination
```

### 5. Final Configuration on the Server

Once files are transferred, connect to your server and:

1. Verify database connection:

```bash
php artisan migrate:status
```

2. Create a symbolic link for storage:

```bash
php artisan storage:link
```

3. Set correct permissions:

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

4. Update DNS settings to point your domain to the server.

## Route Configuration

All routes in the application should continue to work as they did during development. The Apache configuration has been set up to ensure that:

1. All static assets (CSS, JavaScript, images) are properly served with optimized cache settings
2. URL rewriting is properly configured to maintain the same routes
3. Security headers are in place to protect your application

## Monitoring and Maintenance

### Checking Logs

-   Apache logs: `/var/log/apache2/esbtp-error.log` and `/var/log/apache2/esbtp-access.log`
-   Laravel logs: `storage/logs/laravel.log`

### Regular Maintenance

1. Database backups:

```bash
php artisan db:backup
```

2. Updating the application:

```bash
# Pull the latest code
git pull

# Install dependencies
composer install --no-dev --optimize-autoloader

# Clear cache and optimize
php artisan optimize
```

## Troubleshooting

### 404 Page Not Found

-   Check that the Apache rewrite module is enabled
-   Verify that .htaccess files are being processed (AllowOverride All)
-   Ensure the document root is pointing to the 'public' directory

### 500 Server Error

-   Check Apache error logs
-   Temporarily enable detailed error reporting in .env (APP_DEBUG=true)
-   Verify file permissions on storage and bootstrap/cache directories

### Database Connection Issues

-   Verify database credentials in .env
-   Ensure the database server is accessible from the web server
-   Check that the MySQL user has the necessary permissions

### Asset Loading Problems

-   Ensure the asset paths are correct (using relative or absolute URLs as needed)
-   Check file permissions on the public directory
-   Verify that the web server can access the files

## Security Recommendations

1. Set up HTTPS using Let's Encrypt or another SSL certificate provider
2. Enable HSTS headers once SSL is configured
3. Implement a firewall to restrict access to sensitive ports
4. Set up regular security updates for the server
5. Use strong, unique passwords for database and FTP access
6. Consider implementing a Web Application Firewall (WAF)
