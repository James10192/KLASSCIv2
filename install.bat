@echo off
echo === Script d'installation pour ESBTP-yAKRO ===
echo Ce script va configurer l'application apres un git clone
echo.

REM Installation des dependances Composer
echo 1. Installation des dependances Composer...
call composer install
echo.

REM Creation du fichier .env s'il n'existe pas
if not exist .env (
    echo 2. Creation du fichier .env...
    copy .env.example .env
    
    REM Generation de la cle d'application
    echo 3. Generation de la cle d'application...
    php artisan key:generate
) else (
    echo 2. Le fichier .env existe deja.
    echo 3. Generation de la cle d'application (si necessaire)...
    php artisan key:generate
)
echo.

REM Demande des informations de base de donnees
echo 4. Configuration de la base de donnees...
echo Veuillez entrer les informations de connexion a la base de donnees :
echo.

set /p db_name=Nom de la base de donnees : 
set /p db_user=Utilisateur de la base de donnees : 
set /p db_password=Mot de passe de la base de donnees : 
set /p db_host=Hote de la base de donnees (localhost par defaut) : 
if "%db_host%"=="" set db_host=localhost
echo.

REM Mise a jour du fichier .env avec les informations de base de donnees
powershell -Command "(Get-Content .env) -replace 'DB_HOST=.*', 'DB_HOST=%db_host%' | Set-Content .env"
powershell -Command "(Get-Content .env) -replace 'DB_DATABASE=.*', 'DB_DATABASE=%db_name%' | Set-Content .env"
powershell -Command "(Get-Content .env) -replace 'DB_USERNAME=.*', 'DB_USERNAME=%db_user%' | Set-Content .env"
powershell -Command "(Get-Content .env) -replace 'DB_PASSWORD=.*', 'DB_PASSWORD=%db_password%' | Set-Content .env"

REM Execution des migrations et des seeders
echo 5. Execution des migrations...
php artisan migrate --force
echo.

echo 6. Execution des seeders...
php artisan db:seed --force
echo.

REM Installation des dependances npm
echo 7. Installation des dependances npm...
call npm install
echo.

REM Compilation des assets
echo 8. Compilation des assets...
call npm run dev
echo.

REM Effacement du cache
echo 9. Effacement du cache...
php artisan optimize:clear
echo.

echo === Installation terminee ===
echo Vous pouvez maintenant acceder a l'application.
echo N'oubliez pas de configurer votre serveur web pour pointer vers le dossier public.
echo.
pause 