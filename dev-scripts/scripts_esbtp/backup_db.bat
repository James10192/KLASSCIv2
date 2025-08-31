@echo off
echo ===================================================
echo ESBTP Database Backup Script
echo ===================================================
echo.

REM Get current date and time for filename
for /f "tokens=2 delims==" %%a in ('wmic OS Get localdatetime /value') do set "dt=%%a"
set "YYYY=%dt:~0,4%"
set "MM=%dt:~4,2%"
set "DD=%dt:~6,2%"
set "HH=%dt:~8,2%"
set "Min=%dt:~10,2%"
set "Sec=%dt:~12,2%"

set "timestamp=%YYYY%-%MM%-%DD%_%HH%-%Min%-%Sec%"
set "backup_dir=..\storage\app\backups"
set "backup_file=%backup_dir%\esbtp_backup_%timestamp%.sql"

REM Create backup directory if it doesn't exist
if not exist "%backup_dir%" (
    echo Creating backup directory...
    mkdir "%backup_dir%"
)

echo.
echo Reading database configuration from .env file...
cd ..

REM Extract database configuration from .env file
for /f "tokens=1,2 delims==" %%a in (.env) do (
    if "%%a"=="DB_DATABASE" set "DB_DATABASE=%%b"
    if "%%a"=="DB_USERNAME" set "DB_USERNAME=%%b"
    if "%%a"=="DB_PASSWORD" set "DB_PASSWORD=%%b"
    if "%%a"=="DB_HOST" set "DB_HOST=%%b"
    if "%%a"=="DB_PORT" set "DB_PORT=%%b"
)

echo.
echo Creating database backup...
echo Database: %DB_DATABASE%
echo Backup file: %backup_file%
echo.

REM Create the backup using mysqldump
mysqldump --host=%DB_HOST% --port=%DB_PORT% --user=%DB_USERNAME% --password=%DB_PASSWORD% %DB_DATABASE% > "%backup_file%"

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ===================================================
    echo Database backup completed successfully!
    echo ===================================================
    echo.
    echo Backup saved to: %backup_file%
) else (
    echo.
    echo ===================================================
    echo Database backup failed!
    echo ===================================================
    echo.
    echo Please check your database configuration and ensure mysqldump is in your PATH.
)

echo.
cd scripts_esbtp
pause 