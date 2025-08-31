@echo off
echo Running migrations and seeders...

REM Chemin vers PHP - ajustez selon votre installation
SET PHP_PATH=C:\wamp64\bin\php\php8.2.0\php.exe

REM Exécuter le script PHP
"%PHP_PATH%" "%~dp0run_migrations_and_seed.php"

echo.
echo Press any key to exit...
pause > nul 