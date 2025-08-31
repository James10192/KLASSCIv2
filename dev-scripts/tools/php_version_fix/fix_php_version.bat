@echo off
echo ===== WAMP PHP Version Checker and Fixer =====
echo.

REM Check if WAMP directory exists
if not exist "C:\wamp64" (
    echo WAMP directory not found at C:\wamp64
    echo Please adjust the script if your WAMP is installed elsewhere
    goto :end
)

echo Checking available PHP versions in WAMP...
echo.

REM List available PHP versions
set /a count=0
for /d %%i in (C:\wamp64\bin\php\*) do (
    set /a count+=1
    set "php_version[!count!]=%%~ni"
    echo !count!. %%~ni
)

echo.
echo Current PHP version used by WAMP:
C:\wamp64\bin\php\php.exe -v | findstr /B /C:"PHP"
echo.

echo To switch to a different PHP version, restart WAMP and use the WAMP menu:
echo 1. Left-click on WAMP icon in system tray
echo 2. Go to PHP -^> Version
echo 3. Select the desired PHP version (8.0.0 or higher)
echo.

echo After switching PHP version, restart your web server and try the setup again.
echo.

:end
pause 