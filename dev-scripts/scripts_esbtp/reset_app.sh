#!/bin/bash

echo "==================================================="
echo "ESBTP Application Reset Script"
echo "==================================================="
echo
echo "This script will reset the application to its initial state."
echo "It will:"
echo " 1. Clear all caches"
echo " 2. Drop the database (if it exists)"
echo " 3. Remove installation file (if exists)"
echo " 4. Remove .env file and replace with .env.example"
echo
echo "After running this script, you will need to go through the setup process again."
echo
echo "Press Ctrl+C to cancel or Enter to continue..."
read

echo
echo "Clearing caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Sauvegarde des informations de base de données avant de supprimer le fichier .env
echo
echo "Reading database configuration from .env file (if exists)..."
cd ..
DB_HOST="127.0.0.1"
DB_PORT="3306"
DB_USERNAME="root"
DB_PASSWORD=""
DB_DATABASE="smart_school_db"

if [ -f .env ]; then
    # Extraire les informations de base de données du fichier .env
    DB_HOST=$(grep DB_HOST .env | cut -d '=' -f2)
    DB_PORT=$(grep DB_PORT .env | cut -d '=' -f2)
    DB_USERNAME=$(grep DB_USERNAME .env | cut -d '=' -f2)
    DB_PASSWORD=$(grep DB_PASSWORD .env | cut -d '=' -f2)
    DB_DATABASE=$(grep DB_DATABASE .env | cut -d '=' -f2)
fi

echo
echo "Dropping database (if exists)..."
echo "Database: $DB_DATABASE"

# Créer un fichier SQL temporaire pour les commandes
cat > temp_reset.sql << EOF
DROP DATABASE IF EXISTS \`$DB_DATABASE\`;
EOF

# Exécuter les commandes SQL
mysql --host=$DB_HOST --port=$DB_PORT --user=$DB_USERNAME --password=$DB_PASSWORD < temp_reset.sql

# Supprimer le fichier temporaire
rm temp_reset.sql

echo
echo "Removing installation file (if exists)..."
if [ -f "storage/app/installed" ]; then
    rm -f "storage/app/installed"
    echo "Installation file removed successfully."
else
    echo "Installation file not found, application is already in setup mode."
fi

echo
echo "Removing .env file and replacing with .env.example..."
if [ -f ".env" ]; then
    rm -f ".env"
    cp .env.example .env
    echo "Removed .env file and created a fresh one from .env.example."
else
    cp .env.example .env
    echo "Created a fresh .env file from .env.example."
fi

echo
echo "==================================================="
echo "Application reset completed successfully!"
echo "==================================================="
echo
echo "You can now start the application with:"
echo "php artisan serve"
echo
echo "Then navigate to http://localhost:8000 to go through the setup process."
echo
cd scripts_esbtp
read -p "Press Enter to exit..." 