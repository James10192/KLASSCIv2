#!/bin/bash

echo "==================================================="
echo "ESBTP Application Corrections Script"
echo "==================================================="
echo
echo "This script will apply all necessary corrections to make ESBTP-yAKRO fully functional."
echo "It will:"
echo " 1. Update the ESBTPInscriptionService to automatically generate student IDs"
echo " 2. Update the SecretaireAdminController for proper secretary account creation"
echo " 3. Comment out SuperAdminSeeder in DatabaseSeeder"
echo
echo "Press Ctrl+C to cancel or Enter to continue..."
read

# Create backup directory
echo
echo "Creating backups..."
mkdir -p backups/app/Services
mkdir -p backups/app/Http/Controllers/ESBTP
cp app/Services/ESBTPInscriptionService.php backups/app/Services/ 2>/dev/null || echo "ESBTPInscriptionService.php not found, no backup created"
cp app/Http/Controllers/ESBTP/SecretaireAdminController.php backups/app/Http/Controllers/ESBTP/ 2>/dev/null || echo "SecretaireAdminController.php not found, no backup created"

# Copy corrected files
echo
echo "Applying corrections..."
cp tmp/services/ESBTPInscriptionService.php app/Services/
cp tmp/controllers/SecretaireAdminController.php app/Http/Controllers/ESBTP/

# Commenting out SuperAdminSeeder
echo
echo "Updating DatabaseSeeder.php..."

# Create a temporary file to update DatabaseSeeder.php
DBSEEDER="database/seeders/DatabaseSeeder.php"
TMPFILE=$(mktemp)

if [ -f "$DBSEEDER" ]; then
    # Modify the file to comment out SuperAdminSeeder
    cat "$DBSEEDER" | sed -e 's/\(.*SuperAdminSeeder.*\)/\/\/ \1 \/\/ Disabled as per requirements/' > "$TMPFILE"
    cp "$TMPFILE" "$DBSEEDER"
    echo "SuperAdminSeeder has been commented out in DatabaseSeeder.php"
else
    echo "Warning: DatabaseSeeder.php not found. Please manually comment out SuperAdminSeeder."
fi

rm -f "$TMPFILE"

echo
echo "==================================================="
echo "Corrections applied successfully!"
echo "==================================================="
echo
echo "To complete the setup:"
echo "1. Run database migrations and seeders:"
echo "   php artisan migrate:fresh"
echo "   php artisan db:seed"
echo
echo "2. Follow the installation process through the web interface"
echo "   to create your superAdmin account."
echo
echo "3. Test the application with all user roles to ensure functionality."
echo
read -p "Press Enter to exit..." 