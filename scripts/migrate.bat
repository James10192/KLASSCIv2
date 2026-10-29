@echo off
cd %~dp0\..
php artisan migrate
echo Database migrations completed!
pause 