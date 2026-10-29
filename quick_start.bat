@echo off
echo ===================================================
echo    ESBTP - Demarrage rapide de l'application
echo ===================================================
echo.

echo Verification des prerequis...
where php >nul 2>nul
if %errorlevel% neq 0 (
    echo [ERREUR] PHP n'est pas installe ou n'est pas dans le PATH.
    echo Veuillez installer PHP ou ajouter son chemin au PATH.
    pause
    exit /b
)

echo Effacement du cache...
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo.
echo Choisissez le mode de demarrage:
echo 1. Serveur de developpement Laravel (http://127.0.0.1:8000)
echo 2. Serveur Apache/WAMP (http://localhost/smart_school_new)
echo.

set /p mode="Entrez votre choix (1 ou 2): "

if "%mode%"=="1" (
    echo.
    echo Configuration pour le serveur de developpement...
    
    REM Mettre à jour le fichier .env
    powershell -Command "(Get-Content .env) -replace 'APP_URL=.*', 'APP_URL=http://127.0.0.1:8000' | Set-Content .env"
    
    echo.
    echo Demarrage du serveur Laravel...
    echo Le serveur sera accessible a l'adresse: http://127.0.0.1:8000
    echo Appuyez sur Ctrl+C pour arreter le serveur.
    echo.
    
    php artisan serve --host=127.0.0.1 --port=8000
) else if "%mode%"=="2" (
    echo.
    echo Configuration pour le serveur Apache/WAMP...
    
    REM Mettre à jour le fichier .env
    powershell -Command "(Get-Content .env) -replace 'APP_URL=.*', 'APP_URL=http://localhost/smart_school_new/public' | Set-Content .env"
    
    echo.
    echo L'application est configuree pour Apache/WAMP.
    echo Accedez a l'application via: http://localhost/smart_school_new
    echo.
    
    start http://localhost/smart_school_new
    
    pause
) else (
    echo.
    echo [ERREUR] Choix invalide.
    pause
) 