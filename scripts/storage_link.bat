@echo off
cd %~dp0\..
php artisan storage:link
echo Storage link created!
pause 