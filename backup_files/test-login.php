<?php
// Test script to check authentication

// Bootstrap the Laravel application
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Auth;

// Test credentials
$credentials = [
    // Test the username 'superadmin'
    [
        'username' => 'superadmin',
        'password' => 'password123'
    ],
    // Test the generated username with period
    [
        'username' => 'super.admin',
        'password' => 'password123'
    ],
    // Test with email instead of username
    [
        'email' => 'superadmin@esbtp.ci',
        'password' => 'password123'
    ],
    // Test secretaire user
    [
        'username' => 'secretaire.',
        'password' => 'password123'
    ],
    // Try other variations
    [
        'username' => 'secretaire',
        'password' => 'password123'
    ],
    [
        'username' => 'admin',
        'password' => 'password123'
    ]
];

echo "=== Testing Login Credentials ===\n\n";

foreach ($credentials as $cred) {
    echo "Testing: " . json_encode($cred) . "\n";
    
    if (Auth::attempt($cred)) {
        $user = Auth::user();
        echo "✅ SUCCESS: Logged in as {$user->name} (ID: {$user->id})\n";
        echo "   Username: {$user->username}\n";
        echo "   Email: {$user->email}\n";
        Auth::logout();
    } else {
        echo "❌ FAILED: Authentication failed\n";
    }
    echo "\n";
}

// Also check all users with admin permissions
echo "=== Admin Users in Database ===\n\n";

$adminUsers = App\Models\User::whereHas('roles', function($q) {
    $q->where('name', 'superAdmin');
})->get();

foreach ($adminUsers as $admin) {
    echo "Admin User: {$admin->name}\n";
    echo "  ID: {$admin->id}\n";
    echo "  Username: {$admin->username}\n"; 
    echo "  Email: {$admin->email}\n";
    echo "  Password: " . (strlen($admin->password) > 0 ? "Hashed password exists" : "No password set") . "\n";
    echo "\n";
}

// Check the authentication mechanism
echo "=== Authentication Configuration ===\n\n";
echo "Auth driver: " . config('auth.defaults.guard') . "\n";
echo "User provider: " . config('auth.guards.web.provider') . "\n";
echo "Provider model: " . config('auth.providers.users.model') . "\n"; 