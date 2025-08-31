<?php

/**
 * Database Status Check Script
 * 
 * This script helps you check the status of your database tables and records.
 * It's like a health check-up for your database - imagine a doctor checking
 * if all your organs are working properly!
 */

// Display a welcome message
echo "===========================================\n";
echo "      DATABASE STATUS CHECK UTILITY       \n";
echo "===========================================\n";

// Get the path to PHP executable
echo "Enter the full path to your PHP executable (e.g., C:\\wamp64\\bin\\php\\php8.1.31\\php.exe): ";
$handle = fopen("php://stdin", "r");
$phpPath = trim(fgets($handle));

if (!file_exists($phpPath)) {
    echo "Error: PHP executable not found at the specified path.\n";
    exit;
}

echo "\nChecking database status...\n";

// Step 1: Check database connection
echo "\n1. Checking database connection...\n";
$command = "$phpPath artisan db:monitor";
echo "Running: $command\n";
passthru($command, $returnCode);

if ($returnCode !== 0) {
    echo "Warning: The db:monitor command might not be available in your Laravel version.\n";
    echo "Trying alternative method...\n";
    
    $command = "$phpPath artisan tinker --execute=\"try { DB::connection()->getPdo(); echo 'Connection successful!'; } catch (\\Exception \$e) { echo 'Connection failed: ' . \$e->getMessage(); }\"";
    echo "Running: $command\n";
    passthru($command, $returnCode);
}

// Step 2: List migrations status
echo "\n2. Checking migrations status...\n";
$command = "$phpPath artisan migrate:status";
echo "Running: $command\n";
passthru($command, $returnCode);

// Step 3: Count records in key tables
echo "\n3. Counting records in key tables...\n";
$tables = [
    'users' => 'Users',
    'roles' => 'Roles',
    'permissions' => 'Permissions',
    'students' => 'Students',
    'teachers' => 'Teachers',
    'departments' => 'Departments',
    'ufrs' => 'UFRs',
    'formations' => 'Formations',
    'parcours' => 'Parcours',
    'unite_enseignements' => 'Teaching Units',
    'element_constitutifs' => 'Teaching Elements'
];

foreach ($tables as $table => $label) {
    $command = "$phpPath artisan tinker --execute=\"try { echo '$label: ' . DB::table('$table')->count(); } catch (\\Exception \$e) { echo '$label: Table not found or error'; }\"";
    passthru($command, $returnCode);
}

echo "\n===========================================\n";
echo "Database status check completed!\n";
echo "===========================================\n";

// Close the handle
fclose($handle); 