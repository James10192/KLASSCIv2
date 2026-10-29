@echo off
echo ===================================================
echo ESBTP Application Reset Script
echo ===================================================
echo.
echo This script will reset the application to its initial state.
echo It will:
echo  1. Clear all caches
echo  2. Drop the database (if it exists)
echo  3. Remove installation file (if exists)
echo  4. Remove .env file and replace with .env.example
echo  5. Generate a new application key
echo.
echo After running this script, you will need to go through the setup process again.
echo.
echo Press Ctrl+C to cancel or any key to continue...
pause > nul

echo.
echo Clearing caches...
php artisan cache:clear
if %ERRORLEVEL% NEQ 0 (
    echo Failed to clear cache. Continuing anyway...
)
php artisan config:clear
php artisan route:clear
php artisan view:clear

REM Définir des valeurs par défaut pour la base de données
set "DB_DATABASE=smart_school_db"
set "DB_USERNAME=root"
set "DB_PASSWORD="
set "DB_HOST=127.0.0.1"
set "DB_PORT=3306"

echo.
echo Reading database configuration from .env file (if exists)...
if exist ".env" (
    for /f "tokens=1,2 delims==" %%a in (.env) do (
        if "%%a"=="DB_DATABASE" set "DB_DATABASE=%%b"
        if "%%a"=="DB_USERNAME" set "DB_USERNAME=%%b"
        if "%%a"=="DB_PASSWORD" set "DB_PASSWORD=%%b"
        if "%%a"=="DB_HOST" set "DB_HOST=%%b"
        if "%%a"=="DB_PORT" set "DB_PORT=%%b"
    )
)

echo.
echo Dropping database (if exists)...
echo Database: %DB_DATABASE%

REM Vérifier si MySQL est disponible
where mysql >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo MySQL command not found. Skipping database drop.
    echo Please manually drop the database if needed.
) else (
    REM Créer un fichier SQL temporaire pour les commandes
    echo DROP DATABASE IF EXISTS %DB_DATABASE%; > temp_reset.sql

    REM Exécuter les commandes SQL
    mysql --host=%DB_HOST% --port=%DB_PORT% --user=%DB_USERNAME% --password=%DB_PASSWORD% < temp_reset.sql

    REM Supprimer le fichier temporaire
    del temp_reset.sql
)

echo.
echo Removing installation file (if exists)...
if exist "storage\app\installed" (
    del /f /q "storage\app\installed"
    echo Installation file removed successfully.
) else (
    echo Installation file not found, application is already in setup mode.
)

echo.
echo Removing .env file and replacing with .env.example...
if exist ".env" (
    del /f /q ".env"
    copy .env.example .env
    echo Removed .env file and created a fresh one from .env.example.
) else (
    copy .env.example .env
    echo Created a fresh .env file from .env.example.
)

echo.
echo Generating application key...
php artisan key:generate
if %ERRORLEVEL% NEQ 0 (
    echo Failed to generate application key.
    echo You may need to run 'php artisan key:generate' manually after setup.
) else (
    echo Application key generated successfully.
)

echo.
echo ===================================================
echo Application reset completed successfully!
echo ===================================================
echo.
echo You can now start the application with:
echo php artisan serve
echo.
echo Then navigate to http://localhost:8000 to go through the setup process.
echo.
pause 