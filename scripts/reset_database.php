<?php

/**
 * Database Reset PHP Script
 * 
 * This script helps you reset your database completely and run migrations and seeders.
 * It's like giving your database a fresh start - imagine cleaning your room completely
 * and then putting everything back in an organized way!
 */

// Make sure we're running from the command line
if (php_sapi_name() !== 'cli') {
    echo "This script must be run from the command line.";
    exit(1);
}

// Define colors for CLI output
define('COLOR_GREEN', "\033[32m");
define('COLOR_RED', "\033[31m");
define('COLOR_YELLOW', "\033[33m");
define('COLOR_CYAN', "\033[36m");
define('COLOR_RESET', "\033[0m");

// Display a welcome message
echo "===========================================\n";
echo "      DATABASE RESET UTILITY SCRIPT       \n";
echo "===========================================\n";
echo "This script will:\n";
echo "1. Drop all tables in your database\n";
echo "2. Run all migrations\n";
echo "3. Seed the database with initial data\n";
echo "\n";
echo COLOR_RED . "WARNING: This will DELETE ALL DATA in your database!" . COLOR_RESET . "\n";
echo "===========================================\n\n";

// Ask for confirmation
echo "Are you sure you want to continue? (yes/no): ";
$confirmation = trim(fgets(STDIN));
if ($confirmation !== 'yes') {
    echo COLOR_YELLOW . "Operation cancelled.\n" . COLOR_RESET;
    exit(0);
}

// Function to run artisan commands
function runCommand($command) {
    echo "Running: $command\n";
    passthru($command, $returnCode);
    return $returnCode;
}

echo "\n" . COLOR_CYAN . "Resetting database..." . COLOR_RESET . "\n";

// Step 1: Drop all tables
echo COLOR_CYAN . "Step 1: Dropping all tables..." . COLOR_RESET . "\n";
$returnCode = runCommand('php artisan db:wipe --force');
if ($returnCode !== 0) {
    echo COLOR_RED . "Error: Failed to drop tables. Error code: $returnCode\n" . COLOR_RESET;
    exit(1);
}

// Step 2: Run migrations
echo "\n" . COLOR_CYAN . "Step 2: Running migrations..." . COLOR_RESET . "\n";
$returnCode = runCommand('php artisan migrate --force');
if ($returnCode !== 0) {
    echo COLOR_RED . "Error: Failed to run migrations. Error code: $returnCode\n" . COLOR_RESET;
    exit(1);
}

// Step 3: Seed the database
echo "\n" . COLOR_CYAN . "Step 3: Seeding the database..." . COLOR_RESET . "\n";
$returnCode = runCommand('php artisan db:seed --force');
if ($returnCode !== 0) {
    echo COLOR_RED . "Error: Failed to seed the database. Error code: $returnCode\n" . COLOR_RESET;
    exit(1);
}

echo "\n===========================================\n";
echo COLOR_GREEN . "Database reset completed successfully!\n" . COLOR_RESET;
echo "===========================================\n";

// Wait for user input before closing
echo "\nPress Enter to exit...";
fgets(STDIN); 