@echo off
cd %~dp0\..
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
echo Application optimized for production!
pause 