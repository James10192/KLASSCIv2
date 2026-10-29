@echo off
echo ===================================================
echo ESBTP Application Deployment Script
echo ===================================================
echo.

REM Set environment variables
set PROD_ENV_FILE=.env.production
set ENVIRONMENT=production
set BACKUP_DIR=backups\%date:~-4,4%%date:~-7,2%%date:~-10,2%

echo Step 1: Creating backup directory...
if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"

echo Step 2: Backing up current files...
xcopy /E /I /Y "public" "%BACKUP_DIR%\public"
copy ".env" "%BACKUP_DIR%\.env.backup"
echo Database backup...
php artisan db:backup --database=mysql --destination=local --compression=gzip --destination-path="%BACKUP_DIR%"

echo Step 3: Installing production dependencies...
composer install --no-dev --optimize-autoloader

echo Step 4: Copying production environment file...
copy "%PROD_ENV_FILE%" ".env"

echo Step 5: Clearing cache and optimizing application...
php artisan cache:clear
php artisan config:cache
php artisan view:cache
php artisan optimize

echo Step 6: Setting correct permissions...
icacls storage\* /grant "everyone:(OI)(CI)F" /T
icacls bootstrap\cache\* /grant "everyone:(OI)(CI)F" /T

echo ===================================================
echo Deployment preparation completed!
echo ===================================================
echo.
echo Next steps for production:
echo 1. Copy the entire project to the production server
echo 2. Configure the Apache virtual host using apache_vhost.conf
echo 3. Restart Apache
echo 4. Visit the site at http://esbtp.nnagroup.net to verify
echo.
echo FTP Connection Details:
echo Host: ftp.nnagroup.net
echo Username: Marcel@nnagroup.net
echo Port: 21
echo.
pause
