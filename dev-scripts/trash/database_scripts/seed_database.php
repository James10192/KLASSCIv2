<?php

/**
 * Database Seeding Script
 * 
 * This script helps you seed your database with initial data.
 * It's like planting seeds in a garden - you're preparing your database
 * with the initial data it needs to grow into a full application!
 */

// Display a welcome message
echo "===========================================\n";
echo "      DATABASE SEEDING UTILITY SCRIPT     \n";
echo "===========================================\n";
echo "This script will seed your database with initial data.\n";
echo "You can choose which seeders to run.\n";
echo "===========================================\n\n";

// Get the path to PHP executable
echo "Enter the full path to your PHP executable (e.g., C:\\wamp64\\bin\\php\\php8.1.31\\php.exe): ";
$handle = fopen("php://stdin", "r");
$phpPath = trim(fgets($handle));

if (!file_exists($phpPath)) {
    echo "Error: PHP executable not found at the specified path.\n";
    exit;
}

// List available seeders
echo "\nAvailable seeders:\n";
echo "1. All seeders (DatabaseSeeder)\n";
echo "2. Role seeder only\n";
echo "3. User seeder only\n";
echo "4. Super Admin seeder only\n";
echo "5. UFR seeder only\n";
echo "6. Formation seeder only\n";
echo "7. Parcours seeder only\n";
echo "8. Unite Enseignement seeder only\n";
echo "9. Element Constitutif seeder only\n";
echo "10. Exit\n";

// Ask which seeder to run
echo "\nEnter the number of the seeder you want to run (1-10): ";
$choice = trim(fgets($handle));

switch ($choice) {
    case '1':
        echo "\nRunning all seeders...\n";
        $command = "$phpPath artisan db:seed";
        echo "Running: $command\n";
        passthru($command, $returnCode);
        break;
    case '2':
        echo "\nRunning Role seeder...\n";
        $command = "$phpPath artisan db:seed --class=RoleSeeder";
        echo "Running: $command\n";
        passthru($command, $returnCode);
        break;
    case '3':
        echo "\nRunning User seeder...\n";
        $command = "$phpPath artisan db:seed --class=UserSeeder";
        echo "Running: $command\n";
        passthru($command, $returnCode);
        break;
    case '4':
        echo "\nRunning Super Admin seeder...\n";
        $command = "$phpPath artisan db:seed --class=SuperAdminSeeder";
        echo "Running: $command\n";
        passthru($command, $returnCode);
        break;
    case '5':
        echo "\nRunning UFR seeder...\n";
        $command = "$phpPath artisan db:seed --class=UFRSeeder";
        echo "Running: $command\n";
        passthru($command, $returnCode);
        break;
    case '6':
        echo "\nRunning Formation seeder...\n";
        $command = "$phpPath artisan db:seed --class=FormationSeeder";
        echo "Running: $command\n";
        passthru($command, $returnCode);
        break;
    case '7':
        echo "\nRunning Parcours seeder...\n";
        $command = "$phpPath artisan db:seed --class=ParcoursSeeder";
        echo "Running: $command\n";
        passthru($command, $returnCode);
        break;
    case '8':
        echo "\nRunning Unite Enseignement seeder...\n";
        $command = "$phpPath artisan db:seed --class=UniteEnseignementSeeder";
        echo "Running: $command\n";
        passthru($command, $returnCode);
        break;
    case '9':
        echo "\nRunning Element Constitutif seeder...\n";
        $command = "$phpPath artisan db:seed --class=ElementConstitutifSeeder";
        echo "Running: $command\n";
        passthru($command, $returnCode);
        break;
    case '10':
        echo "\nExiting...\n";
        exit;
    default:
        echo "\nInvalid choice. Exiting...\n";
        exit;
}

if ($returnCode !== 0) {
    echo "Error: Failed to run seeder. Error code: $returnCode\n";
    exit;
}

echo "\n===========================================\n";
echo "Database seeding completed successfully!\n";
echo "===========================================\n";

// Close the handle
fclose($handle); 