<?php
/**
 * Auto Migration PHP Script
 * 
 * This script automatically runs migrations without asking for confirmation.
 * It's like having a robot that organizes your room without asking questions!
 * Useful for automated deployments or when you're sure you want to migrate.
 */

// Make sure we're running from the command line
if (php_sapi_name() !== 'cli') {
    echo "This script must be run from the command line.";
    exit(1);
}

// Define colors for CLI output
define('COLOR_GREEN', "\033[32m");
define('COLOR_RED', "\033[31m");
define('COLOR_CYAN', "\033[36m");
define('COLOR_RESET', "\033[0m");

// Display a welcome message
echo "===========================================\n";
echo "      AUTO MIGRATION UTILITY SCRIPT       \n";
echo "===========================================\n";
echo "This script will automatically run migrations.\n";
echo "===========================================\n\n";

// Function to run artisan commands
function runCommand($command) {
    echo "Running: $command\n";
    passthru($command, $returnCode);
    return $returnCode;
}

echo COLOR_CYAN . "Running migrations..." . COLOR_RESET . "\n";

// Run migrations
$returnCode = runCommand('php artisan migrate --force');
if ($returnCode !== 0) {
    echo COLOR_RED . "Error: Failed to run migrations. Error code: $returnCode\n" . COLOR_RESET;
    exit(1);
}

echo "\n===========================================\n";
echo COLOR_GREEN . "Migrations completed successfully!\n" . COLOR_RESET;
echo "===========================================\n"; 