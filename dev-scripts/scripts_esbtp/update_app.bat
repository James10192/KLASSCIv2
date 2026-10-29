@echo off
echo ===================================================
echo ESBTP Application Update Script
echo ===================================================
echo.
echo This script will update the application with the latest changes.
echo It will:
echo  1. Pull the latest code from the repository
echo  2. Install/update PHP dependencies
echo  3. Install/update JavaScript dependencies
echo  4. Compile assets
echo  5. Run new migrations
echo  6. Clear all caches
echo.
echo Press Ctrl+C to cancel or any key to continue...
pause > nul

echo.
echo Pulling latest code...
cd ..
git pull

echo.
echo Updating PHP dependencies...
composer install

echo.
echo Updating JavaScript dependencies...
npm install

echo.
echo Compiling assets...
npm run dev

echo.
echo Running migrations...
php artisan migrate

echo.
echo Clearing caches...
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo.
echo ===================================================
echo Application update completed successfully!
echo ===================================================
echo.
echo You can now start the application with:
echo php artisan serve
echo.
echo Or use the start_app.bat script in the scripts_esbtp folder.
echo.
pause 