@echo off
REM Auto Migration Batch Script

echo ===========================================
echo       AUTO MIGRATION UTILITY SCRIPT       
echo ===========================================
echo This script will automatically run migrations.
echo ===========================================
echo.

REM Use the PHP from WAMP
set PHP_PATH=C:\wamp64\bin\php\php8.2.26\php.exe

if not exist "%PHP_PATH%" (
    echo Error: PHP executable not found at %PHP_PATH%
    echo Please edit this script to set the correct PHP path.
    goto :end
)

echo Using PHP at: %PHP_PATH%
echo.
echo Running migrations...

"%PHP_PATH%" artisan migrate --force
if %ERRORLEVEL% neq 0 (
    echo Error: Failed to run migrations.
    goto :end
)

echo.
echo ===========================================
echo Migrations completed successfully!
echo ===========================================

:end 