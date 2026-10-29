@echo off
REM Database Reset Batch Script

echo ===========================================
echo       DATABASE RESET UTILITY SCRIPT       
echo ===========================================
echo This script will:
echo 1. Drop all tables in your database
echo 2. Run all migrations
echo 3. Seed the database with initial data
echo.
echo WARNING: This will DELETE ALL DATA in your database!
echo ===========================================
echo.

set /p confirmation="Are you sure you want to continue? (yes/no): "
if /i not "%confirmation%"=="yes" (
    echo Operation cancelled.
    goto :end
)

REM Use the PHP from WAMP
set PHP_PATH=C:\wamp64\bin\php\php8.2.26\php.exe

if not exist "%PHP_PATH%" (
    echo Error: PHP executable not found at %PHP_PATH%
    echo Please edit this script to set the correct PHP path.
    goto :end
)

echo.
echo Using PHP at: %PHP_PATH%
echo.
echo Resetting database...

echo Step 1: Dropping all tables...
"%PHP_PATH%" artisan db:wipe --force
if %ERRORLEVEL% neq 0 (
    echo Error: Failed to drop tables.
    goto :end
)

echo.
echo Step 2: Running migrations...
"%PHP_PATH%" artisan migrate --force
if %ERRORLEVEL% neq 0 (
    echo Error: Failed to run migrations.
    goto :end
)

echo.
echo Step 3: Seeding the database...
"%PHP_PATH%" artisan db:seed --force
if %ERRORLEVEL% neq 0 (
    echo Error: Failed to seed the database.
    goto :end
)

echo.
echo ===========================================
echo Database reset completed successfully!
echo ===========================================

:end
pause 