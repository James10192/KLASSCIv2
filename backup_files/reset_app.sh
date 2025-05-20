#!/bin/bash

echo "==================================================="
echo "ESBTP Application Reset Script"
echo "==================================================="
echo
echo "This script will reset the application to its initial state."
echo "It will:"
echo " 1. Clear all caches"
echo " 2. Drop all tables and recreate them"
echo " 3. Run all migrations"
echo " 4. Seed the database with initial data"
echo " 5. Create a superadmin user during installation (not via seeder)"
echo
echo "Press Ctrl+C to cancel or Enter to continue..."
read

echo
echo "Clearing caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo
echo "Resetting database..."
php artisan migrate:fresh

echo
echo "Seeding database with ESBTP data..."
php artisan db:seed
# SuperAdminSeeder a été retiré comme spécifié dans les règles

echo
echo "Generating application key..."
php artisan key:generate

echo
echo "==================================================="
echo "Application reset completed successfully!"
echo "==================================================="
echo
echo "Vous devez maintenant lancer l'installation pour créer un compte superadmin:"
echo "1. Accédez à l'URL de l'application"
echo "2. Suivez les étapes d'installation"
echo "3. Créez un compte superadmin lors de l'installation"
echo
echo "You can now start the application with:"
echo "php artisan serve"
echo
read -p "Press Enter to exit..." 