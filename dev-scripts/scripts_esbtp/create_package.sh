#!/bin/bash

echo "==================================================="
echo "ESBTP Package Creator"
echo "==================================================="
echo
echo "Ce script va créer un package d'installation léger"
echo "pour l'application ESBTP School Management System."
echo
echo "Le package contiendra :"
echo "  1. Le code source de l'application (sans vendor et node_modules)"
echo "  2. Les scripts d'installation et de configuration"
echo "  3. Un guide d'installation rapide"
echo "  4. Les identifiants par défaut"
echo
echo "Appuyez sur Ctrl+C pour annuler ou sur Entrée pour continuer..."
read

# Définir le nom du package avec la date
timestamp=$(date +"%Y%m%d")
package_name="esbtp_package_${timestamp}"
package_dir="../${package_name}"

echo
echo "Création du répertoire pour le package..."
cd ..
if [ -d "$package_name" ]; then
    echo "Le répertoire existe déjà, suppression..."
    rm -rf "$package_name"
fi
mkdir "$package_name"

echo
echo "Copie des fichiers essentiels..."

# Créer la structure de répertoires
mkdir -p "$package_name/app"
mkdir -p "$package_name/bootstrap"
mkdir -p "$package_name/config"
mkdir -p "$package_name/database"
mkdir -p "$package_name/public"
mkdir -p "$package_name/resources"
mkdir -p "$package_name/routes"
mkdir -p "$package_name/storage/app/public"
mkdir -p "$package_name/storage/framework/cache"
mkdir -p "$package_name/storage/framework/sessions"
mkdir -p "$package_name/storage/framework/views"
mkdir -p "$package_name/storage/logs"
mkdir -p "$package_name/scripts_installation"

# Créer un fichier d'exclusion pour rsync
cat > /tmp/exclude_list.txt << EOF
vendor/
node_modules/
.git/
.idea/
.vscode/
storage/framework/cache/*
storage/framework/sessions/*
storage/framework/views/*
storage/logs/*
EOF

# Copier les fichiers et dossiers essentiels
rsync -av --exclude-from=/tmp/exclude_list.txt app/ "$package_name/app/"
rsync -av --exclude-from=/tmp/exclude_list.txt bootstrap/ "$package_name/bootstrap/"
rsync -av --exclude-from=/tmp/exclude_list.txt config/ "$package_name/config/"
rsync -av --exclude-from=/tmp/exclude_list.txt database/ "$package_name/database/"
rsync -av --exclude-from=/tmp/exclude_list.txt public/ "$package_name/public/"
rsync -av --exclude-from=/tmp/exclude_list.txt resources/ "$package_name/resources/"
rsync -av --exclude-from=/tmp/exclude_list.txt routes/ "$package_name/routes/"
rsync -av --exclude-from=/tmp/exclude_list.txt storage/app/public/ "$package_name/storage/app/public/"
cp scripts_esbtp/*.bat "$package_name/scripts_installation/"
cp scripts_esbtp/*.sh "$package_name/scripts_installation/"
cp scripts_esbtp/README.md "$package_name/scripts_installation/"
cp .env.example "$package_name/.env.example"
cp artisan "$package_name/artisan"
cp composer.json "$package_name/composer.json"
cp composer.lock "$package_name/composer.lock"
cp package.json "$package_name/package.json"
cp package-lock.json "$package_name/package-lock.json"
cp README.md "$package_name/README.md"

# Créer un script d'installation rapide pour Windows
cat > "$package_name/install.bat" << 'EOF'
@echo off
echo =================================================
echo Installation de ESBTP School Management System
echo =================================================
echo.
echo Cette installation va :
echo  1. Installer les dépendances PHP (composer)
echo  2. Installer les dépendances JavaScript (npm)
echo  3. Configurer la base de données
echo  4. Exécuter les migrations
echo  5. Créer un utilisateur superadmin
echo.
echo Assurez-vous d'avoir installé :
echo  - PHP 7.4 ou supérieur
echo  - Composer
echo  - MySQL 5.7 ou supérieur
echo  - Node.js et NPM
echo.
echo Appuyez sur une touche pour continuer...
pause > nul

echo.
echo Installation des dépendances PHP...
composer install

echo.
echo Installation des dépendances JavaScript...
npm install
npm run dev

echo.
echo Configuration de l'environnement...
copy .env.example .env
echo.
echo Veuillez configurer votre base de données dans le fichier .env
echo Appuyez sur une touche lorsque vous avez terminé...
pause > nul

echo.
echo Génération de la clé d'application...
php artisan key:generate

echo.
echo Création des tables et alimentation de la base de données...
php artisan migrate:fresh
php artisan db:seed --class=SuperAdminSeeder

echo.
echo Création du lien symbolique pour le stockage...
php artisan storage:link

echo.
echo =================================================
echo Installation terminée avec succès !
echo =================================================
echo.
echo Identifiants superadmin :
echo Email : admin@esbtp.ci
echo Mot de passe : admin123
echo.
echo Pour démarrer l'application, exécutez :
echo php artisan serve
echo.
echo Ou utilisez le script scripts_installation\start_app.bat
echo.
pause
EOF

# Créer un script d'installation pour Linux/Mac
cat > "$package_name/install.sh" << 'EOF'
#!/bin/bash

echo "================================================="
echo "Installation de ESBTP School Management System"
echo "================================================="
echo
echo "Cette installation va :"
echo "  1. Installer les dépendances PHP (composer)"
echo "  2. Installer les dépendances JavaScript (npm)"
echo "  3. Configurer la base de données"
echo "  4. Exécuter les migrations"
echo "  5. Créer un utilisateur superadmin"
echo
echo "Assurez-vous d'avoir installé :"
echo "  - PHP 7.4 ou supérieur"
echo "  - Composer"
echo "  - MySQL 5.7 ou supérieur"
echo "  - Node.js et NPM"
echo
read -p "Appuyez sur Entrée pour continuer..."

echo
echo "Installation des dépendances PHP..."
composer install

echo
echo "Installation des dépendances JavaScript..."
npm install
npm run dev

echo
echo "Configuration de l'environnement..."
cp .env.example .env
echo
echo "Veuillez configurer votre base de données dans le fichier .env"
read -p "Appuyez sur Entrée lorsque vous avez terminé..."

echo
echo "Génération de la clé d'application..."
php artisan key:generate

echo
echo "Création des tables et alimentation de la base de données..."
php artisan migrate:fresh
php artisan db:seed --class=SuperAdminSeeder

echo
echo "Création du lien symbolique pour le stockage..."
php artisan storage:link

echo
echo "================================================="
echo "Installation terminée avec succès !"
echo "================================================="
echo
echo "Identifiants superadmin :"
echo "Email : admin@esbtp.ci"
echo "Mot de passe : admin123"
echo
echo "Pour démarrer l'application, exécutez :"
echo "php artisan serve"
echo
echo "Ou utilisez le script scripts_installation/start_app.sh"
echo
read -p "Appuyez sur Entrée pour quitter..."
EOF

# Rendre le script d'installation exécutable
chmod +x "$package_name/install.sh"

# Créer un guide d'installation rapide
cat > "$package_name/INSTALLATION.md" << 'EOF'
# Guide d'installation rapide

## Prérequis

- PHP 7.4 ou supérieur
- Composer
- MySQL 5.7 ou supérieur
- Node.js et NPM

## Installation rapide

### Windows

1. Exécutez le script `install.bat`
2. Suivez les instructions à l'écran

### Linux/Mac

1. Rendez le script d'installation exécutable : `chmod +x install.sh`
2. Exécutez le script : `./install.sh`
3. Suivez les instructions à l'écran

## Configuration manuelle

Si vous préférez installer l'application manuellement, suivez ces étapes :

1. Installez les dépendances PHP : `composer install`
2. Installez les dépendances JavaScript : `npm install && npm run dev`
3. Copiez le fichier d'environnement : `cp .env.example .env` (Linux/Mac) ou `copy .env.example .env` (Windows)
4. Configurez votre base de données dans le fichier `.env`
5. Générez une clé d'application : `php artisan key:generate`
6. Exécutez les migrations et les seeders : `php artisan migrate:fresh && php artisan db:seed --class=SuperAdminSeeder`
7. Créez un lien symbolique pour le stockage : `php artisan storage:link`

## Démarrage de l'application

Pour démarrer l'application, exécutez : `php artisan serve`

L'application sera accessible à l'adresse : http://localhost:8000

## Identifiants par défaut

### Superadmin
- Email : admin@esbtp.ci
- Mot de passe : admin123

## Scripts utilitaires

Des scripts utilitaires sont disponibles dans le dossier `scripts_installation` pour vous aider à gérer l'application.
Consultez le fichier `scripts_installation/README.md` pour plus d'informations.
EOF

# Créer une archive du package
echo
echo "Création de l'archive..."
tar -czf "${package_name}.tar.gz" "$package_name"

echo
echo "==================================================="
echo "Package créé avec succès !"
echo "==================================================="
echo
echo "Le package a été créé dans le dossier : $package_name"
echo "Une archive a également été créée : ${package_name}.tar.gz"
echo
echo "Vous pouvez maintenant distribuer ce package à vos clients."
echo
cd scripts_esbtp
read -p "Appuyez sur Entrée pour quitter..." 