@echo off
cd %~dp0\..
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo All caches cleared successfully!
pause 