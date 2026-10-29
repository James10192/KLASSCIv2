<?php

/**
 * Database Reset Script
 * 
 * This script helps you reset your database completely and run migrations and seeders.
 * It's like giving your database a fresh start - imagine cleaning your room completely
 * and then putting everything back in an organized way!
 */

// Display a welcome message
echo "===========================================\n";
echo "      DATABASE RESET UTILITY SCRIPT       \n";
echo "===========================================\n";
echo "This script will:\n";
echo "1. Drop all tables in your database\n";
echo "2. Run all migrations\n";
echo "3. Seed the database with initial data\n";
echo "\n";
echo "WARNING: This will DELETE ALL DATA in your database!\n";
echo "===========================================\n\n";

// Ask for confirmation
echo "Are you sure you want to continue? (yes/no): ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
if (strtolower($line) !== 'yes') {
    echo "Operation cancelled.\n";
    exit;
}

// Get the path to PHP executable
echo "\nEnter the full path to your PHP executable (e.g., C:\\wamp64\\bin\\php\\php8.1.31\\php.exe): ";
$phpPath = trim(fgets($handle));

if (!file_exists($phpPath)) {
    echo "Error: PHP executable not found at the specified path.\n";
    exit;
}

echo "\nResetting database...\n";

// Step 1: Drop all tables
echo "Step 1: Dropping all tables...\n";
$command = "$phpPath artisan db:wipe --force";
echo "Running: $command\n";
passthru($command, $returnCode);

if ($returnCode !== 0) {
    echo "Error: Failed to drop tables. Error code: $returnCode\n";
    exit;
}

// Step 2: Run migrations
echo "\nStep 2: Running migrations...\n";
$command = "$phpPath artisan migrate";
echo "Running: $command\n";
passthru($command, $returnCode);

if ($returnCode !== 0) {
    echo "Error: Failed to run migrations. Error code: $returnCode\n";
    exit;
}

// Step 3: Seed the database
echo "\nStep 3: Seeding the database...\n";
$command = "$phpPath artisan db:seed";
echo "Running: $command\n";
passthru($command, $returnCode);

if ($returnCode !== 0) {
    echo "Error: Failed to seed the database. Error code: $returnCode\n";
    exit;
}

echo "\n===========================================\n";
echo "Database reset completed successfully!\n";
echo "===========================================\n";

// Close the handle
fclose($handle); 