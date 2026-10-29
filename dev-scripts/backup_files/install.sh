#!/bin/bash

# Script d'installation pour ESBTP-yAKRO
echo "=== Script d'installation pour ESBTP-yAKRO ==="
echo "Ce script va configurer l'application après un git clone"

# Installation des dépendances Composer
echo "1. Installation des dépendances Composer..."
composer install

# Création du fichier .env s'il n'existe pas
if [ ! -f .env ]; then
    echo "2. Création du fichier .env..."
    cp .env.example .env
    
    # Génération de la clé d'application
    echo "3. Génération de la clé d'application..."
    php artisan key:generate
else
    echo "2. Le fichier .env existe déjà."
    echo "3. Génération de la clé d'application (si nécessaire)..."
    php artisan key:generate
fi

# Demande des informations de base de données
echo "4. Configuration de la base de données..."
echo "Veuillez entrer les informations de connexion à la base de données :"

read -p "Nom de la base de données : " db_name
read -p "Utilisateur de la base de données : " db_user
read -s -p "Mot de passe de la base de données : " db_password
echo ""
read -p "Hôte de la base de données (localhost par défaut) : " db_host
db_host=${db_host:-localhost}

# Mise à jour du fichier .env avec les informations de base de données
sed -i "s/DB_HOST=.*/DB_HOST=$db_host/" .env
sed -i "s/DB_DATABASE=.*/DB_DATABASE=$db_name/" .env
sed -i "s/DB_USERNAME=.*/DB_USERNAME=$db_user/" .env
sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$db_password/" .env

# Exécution des migrations et des seeders
echo "5. Exécution des migrations..."
php artisan migrate --force

echo "6. Exécution des seeders..."
php artisan db:seed --force

# Installation des dépendances npm
echo "7. Installation des dépendances npm..."
npm install

# Compilation des assets
echo "8. Compilation des assets..."
npm run dev

# Effacement du cache
echo "9. Effacement du cache..."
php artisan optimize:clear

echo "=== Installation terminée ==="
echo "Vous pouvez maintenant accéder à l'application."
echo "N'oubliez pas de configurer votre serveur web pour pointer vers le dossier public." 