@echo off
cd %~dp0\..

echo === Laravel Environment Check ===
echo.

REM Check PHP version
echo Checking PHP version...
php -v | findstr /R "PHP [0-9\.]+"

REM Check Laravel version
echo Checking Laravel version...
php artisan --version

REM Check database connection
echo Checking database connection...
php artisan db:show

REM Check storage permissions
echo Checking storage permissions...
if exist storage\logs (
    echo Storage: Permissions OK
) else (
    echo Storage: Permissions issue
)

REM Check if .env file exists
echo Checking .env file...
if exist .env (
    echo .env file: Found
) else (
    echo .env file: Not found
)

REM Check if key is generated
findstr /C:"APP_KEY=base64:" .env >nul
if %errorlevel% equ 0 (
    echo Application key: Generated
) else (
    echo Application key: Not generated
)

echo.
echo === Environment Check Complete ===
pause 