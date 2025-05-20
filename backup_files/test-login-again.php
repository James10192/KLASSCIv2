<?php
// Login test script

// Bootstrap the Laravel application
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Auth;

// Test credentials from the fix-user-credentials.php output
$credentials = [
    [
        'username' => 'superadmin_1',
        'password' => 'password123',
        'role' => 'superAdmin'
    ],
    [
        'username' => 'secretaire',
        'password' => 'password123',
        'role' => 'secretaire'
    ],
    [
        'username' => 'etudiant',
        'password' => 'password123',
        'role' => 'etudiant'
    ],
    [
        'username' => 'enseignant_1',
        'password' => 'password123',
        'role' => 'teacher'
    ]
];

echo "=== Testing Login With Fixed Credentials ===\n\n";

foreach ($credentials as $cred) {
    echo "Testing: " . json_encode($cred) . "\n";
    
    // Try with username
    $loginAttempt = Auth::attempt([
        'username' => $cred['username'],
        'password' => $cred['password']
    ]);
    
    if ($loginAttempt) {
        $user = Auth::user();
        echo "✅ SUCCESS: Logged in as {$user->name} (ID: {$user->id})\n";
        echo "   Username: {$user->username}\n";
        echo "   Email: {$user->email}\n";
        echo "   Roles: " . implode(', ', $user->getRoleNames()->toArray()) . "\n";
        Auth::logout();
    } else {
        echo "❌ FAILED: Authentication failed with username\n";
    }
    echo "\n";
}

echo "=== Login Instructions ===\n\n";
echo "You can now log in with the following credentials:\n\n";

foreach ($credentials as $cred) {
    echo "{$cred['role']}:\n";
    echo "  Username: {$cred['username']}\n";
    echo "  Password: {$cred['password']}\n\n";
}

echo "Make sure to use the username (not email) when logging in.\n"; 