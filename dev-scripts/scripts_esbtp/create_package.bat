@echo off
echo ===================================================
echo ESBTP Package Creator
echo ===================================================
echo.
echo Ce script va créer un package d'installation léger 
echo pour l'application ESBTP School Management System.
echo.
echo Le package contiendra :
echo  1. Le code source de l'application (sans vendor et node_modules)
echo  2. Les scripts d'installation et de configuration
echo  3. Un guide d'installation rapide
echo  4. Les identifiants par défaut
echo.
echo Press Ctrl+C to cancel or any key to continue...
pause > nul

REM Définir le nom du package avec la date
for /f "tokens=2 delims==" %%a in ('wmic OS Get localdatetime /value') do set "dt=%%a"
set "YYYY=%dt:~0,4%"
set "MM=%dt:~4,2%"
set "DD=%dt:~6,2%"
set "timestamp=%YYYY%%MM%%DD%"
set "package_name=esbtp_package_%timestamp%"
set "package_dir=..\%package_name%"

echo.
echo Création du répertoire pour le package...
cd ..
if exist "%package_name%" (
    echo Le répertoire existe déjà, suppression...
    rmdir /s /q "%package_name%"
)
mkdir "%package_name%"

echo.
echo Copie des fichiers essentiels...

REM Créer la structure de répertoires
mkdir "%package_name%\app"
mkdir "%package_name%\bootstrap"
mkdir "%package_name%\config"
mkdir "%package_name%\database"
mkdir "%package_name%\public"
mkdir "%package_name%\resources"
mkdir "%package_name%\routes"
mkdir "%package_name%\storage"
mkdir "%package_name%\scripts_installation"

REM Copier les fichiers et dossiers essentiels
xcopy "app" "%package_name%\app" /E /H /C /I
xcopy "bootstrap" "%package_name%\bootstrap" /E /H /C /I
xcopy "config" "%package_name%\config" /E /H /C /I
xcopy "database" "%package_name%\database" /E /H /C /I
xcopy "public" "%package_name%\public" /E /H /C /I /EXCLUDE:scripts_esbtp\exclude_list.txt
xcopy "resources" "%package_name%\resources" /E /H /C /I
xcopy "routes" "%package_name%\routes" /E /H /C /I
xcopy "storage\app\public" "%package_name%\storage\app\public" /E /H /C /I
xcopy "storage\framework" "%package_name%\storage\framework" /E /H /C /I /EXCLUDE:scripts_esbtp\exclude_list.txt
xcopy "scripts_esbtp\*.bat" "%package_name%\scripts_installation\" /Y
xcopy "scripts_esbtp\*.sh" "%package_name%\scripts_installation\" /Y
xcopy "scripts_esbtp\README.md" "%package_name%\scripts_installation\" /Y
copy ".env.example" "%package_name%\.env.example"
copy "artisan" "%package_name%\artisan"
copy "composer.json" "%package_name%\composer.json"
copy "composer.lock" "%package_name%\composer.lock"
copy "package.json" "%package_name%\package.json"
copy "package-lock.json" "%package_name%\package-lock.json"
copy "README.md" "%package_name%\README.md"

REM Créer un fichier d'exclusion pour xcopy
echo node_modules > scripts_esbtp\exclude_list.txt
echo vendor >> scripts_esbtp\exclude_list.txt
echo .git >> scripts_esbtp\exclude_list.txt
echo .idea >> scripts_esbtp\exclude_list.txt
echo .vscode >> scripts_esbtp\exclude_list.txt
echo storage\framework\cache\* >> scripts_esbtp\exclude_list.txt
echo storage\framework\sessions\* >> scripts_esbtp\exclude_list.txt
echo storage\framework\views\* >> scripts_esbtp\exclude_list.txt
echo storage\logs\* >> scripts_esbtp\exclude_list.txt

REM Créer un script d'installation rapide
echo @echo off > "%package_name%\install.bat"
echo echo ================================================= >> "%package_name%\install.bat"
echo echo Installation de ESBTP School Management System >> "%package_name%\install.bat"
echo echo ================================================= >> "%package_name%\install.bat"
echo echo. >> "%package_name%\install.bat"
echo echo Cette installation va : >> "%package_name%\install.bat"
echo echo  1. Installer les dépendances PHP (composer) >> "%package_name%\install.bat"
echo echo  2. Installer les dépendances JavaScript (npm) >> "%package_name%\install.bat"
echo echo  3. Configurer la base de données >> "%package_name%\install.bat"
echo echo  4. Exécuter les migrations >> "%package_name%\install.bat"
echo echo  5. Créer un utilisateur superadmin >> "%package_name%\install.bat"
echo echo. >> "%package_name%\install.bat"
echo echo Assurez-vous d'avoir installé : >> "%package_name%\install.bat"
echo echo  - PHP 7.4 ou supérieur >> "%package_name%\install.bat"
echo echo  - Composer >> "%package_name%\install.bat"
echo echo  - MySQL 5.7 ou supérieur >> "%package_name%\install.bat"
echo echo  - Node.js et NPM >> "%package_name%\install.bat"
echo echo. >> "%package_name%\install.bat"
echo echo Appuyez sur une touche pour continuer... >> "%package_name%\install.bat"
echo pause ^> nul >> "%package_name%\install.bat"
echo. >> "%package_name%\install.bat"
echo echo Installation des dépendances PHP... >> "%package_name%\install.bat"
echo composer install >> "%package_name%\install.bat"
echo. >> "%package_name%\install.bat"
echo echo Installation des dépendances JavaScript... >> "%package_name%\install.bat"
echo npm install >> "%package_name%\install.bat"
echo npm run dev >> "%package_name%\install.bat"
echo. >> "%package_name%\install.bat"
echo echo Configuration de l'environnement... >> "%package_name%\install.bat"
echo copy .env.example .env >> "%package_name%\install.bat"
echo echo. >> "%package_name%\install.bat"
echo echo Veuillez configurer votre base de données dans le fichier .env >> "%package_name%\install.bat"
echo echo Appuyez sur une touche lorsque vous avez terminé... >> "%package_name%\install.bat"
echo pause ^> nul >> "%package_name%\install.bat"
echo. >> "%package_name%\install.bat"
echo echo Génération de la clé d'application... >> "%package_name%\install.bat"
echo php artisan key:generate >> "%package_name%\install.bat"
echo. >> "%package_name%\install.bat"
echo echo Création des tables et alimentation de la base de données... >> "%package_name%\install.bat"
echo php artisan migrate:fresh >> "%package_name%\install.bat"
echo php artisan db:seed --class=SuperAdminSeeder >> "%package_name%\install.bat"
echo. >> "%package_name%\install.bat"
echo echo Création du lien symbolique pour le stockage... >> "%package_name%\install.bat"
echo php artisan storage:link >> "%package_name%\install.bat"
echo. >> "%package_name%\install.bat"
echo echo ================================================= >> "%package_name%\install.bat"
echo echo Installation terminée avec succès ! >> "%package_name%\install.bat"
echo echo ================================================= >> "%package_name%\install.bat"
echo echo. >> "%package_name%\install.bat"
echo echo Identifiants superadmin : >> "%package_name%\install.bat"
echo echo Email : admin@esbtp.ci >> "%package_name%\install.bat"
echo echo Mot de passe : admin123 >> "%package_name%\install.bat"
echo echo. >> "%package_name%\install.bat"
echo echo Pour démarrer l'application, exécutez : >> "%package_name%\install.bat"
echo echo php artisan serve >> "%package_name%\install.bat"
echo echo. >> "%package_name%\install.bat"
echo echo Ou utilisez le script scripts_installation\start_app.bat >> "%package_name%\install.bat"
echo echo. >> "%package_name%\install.bat"
echo pause >> "%package_name%\install.bat"

REM Créer un script d'installation pour Linux/Mac
echo #!/bin/bash > "%package_name%\install.sh"
echo echo "=================================================" >> "%package_name%\install.sh"
echo echo "Installation de ESBTP School Management System" >> "%package_name%\install.sh"
echo echo "=================================================" >> "%package_name%\install.sh"
echo echo >> "%package_name%\install.sh"
echo echo "Cette installation va :" >> "%package_name%\install.sh"
echo echo "  1. Installer les dépendances PHP (composer)" >> "%package_name%\install.sh"
echo echo "  2. Installer les dépendances JavaScript (npm)" >> "%package_name%\install.sh"
echo echo "  3. Configurer la base de données" >> "%package_name%\install.sh"
echo echo "  4. Exécuter les migrations" >> "%package_name%\install.sh"
echo echo "  5. Créer un utilisateur superadmin" >> "%package_name%\install.sh"
echo echo >> "%package_name%\install.sh"
echo echo "Assurez-vous d'avoir installé :" >> "%package_name%\install.sh"
echo echo "  - PHP 7.4 ou supérieur" >> "%package_name%\install.sh"
echo echo "  - Composer" >> "%package_name%\install.sh"
echo echo "  - MySQL 5.7 ou supérieur" >> "%package_name%\install.sh"
echo echo "  - Node.js et NPM" >> "%package_name%\install.sh"
echo echo >> "%package_name%\install.sh"
echo read -p "Appuyez sur Entrée pour continuer..." >> "%package_name%\install.sh"
echo >> "%package_name%\install.sh"
echo echo "Installation des dépendances PHP..." >> "%package_name%\install.sh"
echo composer install >> "%package_name%\install.sh"
echo >> "%package_name%\install.sh"
echo echo "Installation des dépendances JavaScript..." >> "%package_name%\install.sh"
echo npm install >> "%package_name%\install.sh"
echo npm run dev >> "%package_name%\install.sh"
echo >> "%package_name%\install.sh"
echo echo "Configuration de l'environnement..." >> "%package_name%\install.sh"
echo cp .env.example .env >> "%package_name%\install.sh"
echo echo >> "%package_name%\install.sh"
echo echo "Veuillez configurer votre base de données dans le fichier .env" >> "%package_name%\install.sh"
echo read -p "Appuyez sur Entrée lorsque vous avez terminé..." >> "%package_name%\install.sh"
echo >> "%package_name%\install.sh"
echo echo "Génération de la clé d'application..." >> "%package_name%\install.sh"
echo php artisan key:generate >> "%package_name%\install.sh"
echo >> "%package_name%\install.sh"
echo echo "Création des tables et alimentation de la base de données..." >> "%package_name%\install.sh"
echo php artisan migrate:fresh >> "%package_name%\install.sh"
echo php artisan db:seed --class=SuperAdminSeeder >> "%package_name%\install.sh"
echo >> "%package_name%\install.sh"
echo echo "Création du lien symbolique pour le stockage..." >> "%package_name%\install.sh"
echo php artisan storage:link >> "%package_name%\install.sh"
echo >> "%package_name%\install.sh"
echo echo "=================================================" >> "%package_name%\install.sh"
echo echo "Installation terminée avec succès !" >> "%package_name%\install.sh"
echo echo "=================================================" >> "%package_name%\install.sh"
echo echo >> "%package_name%\install.sh"
echo echo "Identifiants superadmin :" >> "%package_name%\install.sh"
echo echo "Email : admin@esbtp.ci" >> "%package_name%\install.sh"
echo echo "Mot de passe : admin123" >> "%package_name%\install.sh"
echo echo >> "%package_name%\install.sh"
echo echo "Pour démarrer l'application, exécutez :" >> "%package_name%\install.sh"
echo echo "php artisan serve" >> "%package_name%\install.sh"
echo echo >> "%package_name%\install.sh"
echo echo "Ou utilisez le script scripts_installation/start_app.sh" >> "%package_name%\install.sh"
echo echo >> "%package_name%\install.sh"
echo read -p "Appuyez sur Entrée pour quitter..." >> "%package_name%\install.sh"

REM Créer un guide d'installation rapide
echo # Guide d'installation rapide > "%package_name%\INSTALLATION.md"
echo. >> "%package_name%\INSTALLATION.md"
echo ## Prérequis >> "%package_name%\INSTALLATION.md"
echo. >> "%package_name%\INSTALLATION.md"
echo - PHP 7.4 ou supérieur >> "%package_name%\INSTALLATION.md"
echo - Composer >> "%package_name%\INSTALLATION.md"
echo - MySQL 5.7 ou supérieur >> "%package_name%\INSTALLATION.md"
echo - Node.js et NPM >> "%package_name%\INSTALLATION.md"
echo. >> "%package_name%\INSTALLATION.md"
echo ## Installation rapide >> "%package_name%\INSTALLATION.md"
echo. >> "%package_name%\INSTALLATION.md"
echo ### Windows >> "%package_name%\INSTALLATION.md"
echo. >> "%package_name%\INSTALLATION.md"
echo 1. Exécutez le script `install.bat` >> "%package_name%\INSTALLATION.md"
echo 2. Suivez les instructions à l'écran >> "%package_name%\INSTALLATION.md"
echo. >> "%package_name%\INSTALLATION.md"
echo ### Linux/Mac >> "%package_name%\INSTALLATION.md"
echo. >> "%package_name%\INSTALLATION.md"
echo 1. Rendez le script d'installation exécutable : `chmod +x install.sh` >> "%package_name%\INSTALLATION.md"
echo 2. Exécutez le script : `./install.sh` >> "%package_name%\INSTALLATION.md"
echo 3. Suivez les instructions à l'écran >> "%package_name%\INSTALLATION.md"
echo. >> "%package_name%\INSTALLATION.md"
echo ## Configuration manuelle >> "%package_name%\INSTALLATION.md"
echo. >> "%package_name%\INSTALLATION.md"
echo Si vous préférez installer l'application manuellement, suivez ces étapes : >> "%package_name%\INSTALLATION.md"
echo. >> "%package_name%\INSTALLATION.md"
echo 1. Installez les dépendances PHP : `composer install` >> "%package_name%\INSTALLATION.md"
echo 2. Installez les dépendances JavaScript : `npm install && npm run dev` >> "%package_name%\INSTALLATION.md"
echo 3. Copiez le fichier d'environnement : `cp .env.example .env` (Linux/Mac) ou `copy .env.example .env` (Windows) >> "%package_name%\INSTALLATION.md"
echo 4. Configurez votre base de données dans le fichier `.env` >> "%package_name%\INSTALLATION.md"
echo 5. Générez une clé d'application : `php artisan key:generate` >> "%package_name%\INSTALLATION.md"
echo 6. Exécutez les migrations et les seeders : `php artisan migrate:fresh && php artisan db:seed --class=SuperAdminSeeder` >> "%package_name%\INSTALLATION.md"
echo 7. Créez un lien symbolique pour le stockage : `php artisan storage:link` >> "%package_name%\INSTALLATION.md"
echo. >> "%package_name%\INSTALLATION.md"
echo ## Démarrage de l'application >> "%package_name%\INSTALLATION.md"
echo. >> "%package_name%\INSTALLATION.md"
echo Pour démarrer l'application, exécutez : `php artisan serve` >> "%package_name%\INSTALLATION.md"
echo. >> "%package_name%\INSTALLATION.md"
echo L'application sera accessible à l'adresse : http://localhost:8000 >> "%package_name%\INSTALLATION.md"
echo. >> "%package_name%\INSTALLATION.md"
echo ## Identifiants par défaut >> "%package_name%\INSTALLATION.md"
echo. >> "%package_name%\INSTALLATION.md"
echo ### Superadmin >> "%package_name%\INSTALLATION.md"
echo - Email : admin@esbtp.ci >> "%package_name%\INSTALLATION.md"
echo - Mot de passe : admin123 >> "%package_name%\INSTALLATION.md"
echo. >> "%package_name%\INSTALLATION.md"
echo ## Scripts utilitaires >> "%package_name%\INSTALLATION.md"
echo. >> "%package_name%\INSTALLATION.md"
echo Des scripts utilitaires sont disponibles dans le dossier `scripts_installation` pour vous aider à gérer l'application. >> "%package_name%\INSTALLATION.md"
echo Consultez le fichier `scripts_installation/README.md` pour plus d'informations. >> "%package_name%\INSTALLATION.md"

REM Créer un fichier ZIP du package
echo.
echo Création du fichier ZIP...
powershell Compress-Archive -Path "%package_name%" -DestinationPath "%package_name%.zip" -Force

echo.
echo ===================================================
echo Package créé avec succès !
echo ===================================================
echo.
echo Le package a été créé dans le dossier : %package_name%
echo Un fichier ZIP a également été créé : %package_name%.zip
echo.
echo Vous pouvez maintenant distribuer ce package à vos clients.
echo.
cd scripts_esbtp
pause 