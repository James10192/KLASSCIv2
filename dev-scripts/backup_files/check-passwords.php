<?php
// Password checking script

// Bootstrap the Laravel application
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Hash;
use App\Models\User;

echo "=== Checking Passwords in Database ===\n\n";

// Get all admin users and check their passwords
$users = User::where('username', '=', 'superadmin')
    ->orWhere('username', '=', 'admin')
    ->orWhere('email', 'like', '%esbtp.ci%')
    ->select('id', 'name', 'email', 'username', 'password')
    ->get();

echo "Found " . count($users) . " users to test\n\n";

foreach ($users as $user) {
    echo "User: {$user->name} (ID: {$user->id})\n";
    echo "  Username: {$user->username}\n";
    echo "  Email: {$user->email}\n";
    
    // Check if password123 would work with this user's hash
    $testPassword = 'password123';
    $testResult = Hash::check($testPassword, $user->password);
    
    echo "  Password '{$testPassword}' is " . ($testResult ? 'CORRECT ✅' : 'INCORRECT ❌') . "\n";
    
    // If not, let's try some other common passwords
    if (!$testResult) {
        $commonPasswords = ['secret', 'admin123', 'Admin@123', 'Secret@123', 'password'];
        
        foreach ($commonPasswords as $password) {
            $result = Hash::check($password, $user->password);
            if ($result) {
                echo "  Alternative password '{$password}' is CORRECT ✅\n";
                break;
            }
        }
    }
    
    echo "\n";
}

// Let's also try to create a test user with known password to verify hashing works
echo "=== Creating Test User to Verify Hashing ===\n\n";

try {
    $testUser = User::where('email', 'test_auth@example.com')->first();
    
    if (!$testUser) {
        $testUser = new User();
        $testUser->name = 'Test Auth User';
        $testUser->email = 'test_auth@example.com';
        $testUser->username = 'test_auth';
        $testUser->password = Hash::make('password123');
        $testUser->save();
        
        echo "Created test user with username 'test_auth' and password 'password123'\n";
    } else {
        echo "Test user already exists\n";
    }
    
    // Verify the newly created hash
    $freshUser = User::where('email', 'test_auth@example.com')->first();
    $verifyHash = Hash::check('password123', $freshUser->password);
    
    echo "Verifying hash: " . ($verifyHash ? 'WORKS ✅' : 'FAILED ❌') . "\n";
    echo "Hash: " . $freshUser->password . "\n\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Finally, let's check the exact format of the stored hashes
echo "=== Password Hash Analysis ===\n\n";

foreach ($users as $user) {
    echo "User: {$user->name} (ID: {$user->id})\n";
    echo "  Hash: " . $user->password . "\n";
    echo "  Hash Length: " . strlen($user->password) . " characters\n";
    echo "  Hash Format: " . (preg_match('/^\$2y\$/', $user->password) ? 'Bcrypt (Laravel Default)' : 'Unknown/Custom') . "\n";
    echo "\n";
} 