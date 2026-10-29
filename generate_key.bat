@echo off
echo ===================================================
echo ESBTP Application Key Generator
echo ===================================================
echo.
echo This script will generate a new application key for your Laravel application.
echo.

php artisan key:generate

echo.
echo If the key was generated successfully, you can now start the application with:
echo php artisan serve
echo.
pause 