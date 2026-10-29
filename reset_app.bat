@echo off
echo ===================================================
echo ESBTP Application Reset Script
echo ===================================================
echo.
echo This script will reset the application to its initial state.
echo It will:
echo  1. Clear all caches
echo  2. Drop all tables and recreate them
echo  3. Run all migrations
echo  4. Seed the database with initial data
echo  5. Create a superadmin user
echo.
echo Press Ctrl+C to cancel or any key to continue...
pause > nul

echo.
echo Clearing caches...
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo.
echo Resetting database...
php artisan migrate:fresh

echo.
echo Seeding database...
php artisan db:seed --class=SuperAdminSeeder

echo.
echo Generating application key...
php artisan key:generate

echo.
echo ===================================================
echo Application reset completed successfully!
echo ===================================================
echo.
echo Superadmin credentials:
echo Email: admin@esbtp.ci
echo Password: admin123
echo.
echo You can now start the application with:
echo php artisan serve
echo.
pause 