<?php

// Script to reset the password for a superAdmin user
// Run with: php reset_admin_password.php

// Initialize Laravel application
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Get parameters
$username = 'ruben';
$newPassword = 'password123'; // Default new password

echo "=== RESET ADMIN PASSWORD ===\n\n";

// Find the user
try {
    $user = \App\Models\User::where('username', $username)
        ->orWhere('email', $username)
        ->first();

    if ($user) {
        echo "✅ User found:\n";
        echo "   ID: {$user->id}\n";
        echo "   Name: {$user->name}\n";
        echo "   Username: {$user->username}\n";
        echo "   Email: {$user->email}\n";
        
        // Check if user has superAdmin role
        if (method_exists($user, 'hasRole') && $user->hasRole('superAdmin')) {
            echo "   Role: superAdmin\n";
        } else {
            echo "   Warning: User does not have superAdmin role\n";
            
            // Check if role exists
            if (class_exists('\Spatie\Permission\Models\Role')) {
                $role = \Spatie\Permission\Models\Role::where('name', 'superAdmin')->first();
                if ($role) {
                    echo "   Assigning superAdmin role...\n";
                    $user->assignRole('superAdmin');
                    echo "   ✅ Role assigned successfully\n";
                } else {
                    echo "   Creating superAdmin role...\n";
                    $role = \Spatie\Permission\Models\Role::create(['name' => 'superAdmin']);
                    $user->assignRole('superAdmin');
                    echo "   ✅ Role created and assigned successfully\n";
                }
            }
        }
        
        // Reset the password
        $user->password = \Illuminate\Support\Facades\Hash::make($newPassword);
        $user->save();
        
        echo "✅ Password reset successfully to: $newPassword\n";
        
        // Test authentication
        $credentials = [
            'email' => $user->email,
            'password' => $newPassword
        ];
        
        if (\Illuminate\Support\Facades\Auth::attempt($credentials)) {
            echo "✅ Authentication test successful with new password\n";
        } else {
            echo "❌ Authentication test failed. Further investigation required.\n";
            
            // Check auth configuration
            echo "\nAuthentication configuration:\n";
            $authConfig = config('auth');
            echo "   Default guard: " . $authConfig['defaults']['guard'] . "\n";
            echo "   User provider: " . $authConfig['guards'][$authConfig['defaults']['guard']]['provider'] . "\n";
            echo "   Provider model: " . $authConfig['providers'][$authConfig['guards'][$authConfig['defaults']['guard']]['provider']]['model'] . "\n";
        }
    } else {
        echo "❌ User '$username' not found in database\n";
        
        // List all users
        echo "\nExisting users:\n";
        $users = \App\Models\User::all();
        foreach ($users as $u) {
            echo "- {$u->name} (username: {$u->username}, email: {$u->email})\n";
        }
        
        // Create user if needed
        echo "\nDo you want to create a new superAdmin user? [y/n] ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        if (trim($line) == 'y') {
            $user = new \App\Models\User();
            $user->name = 'SuperAdmin';
            $user->username = $username;
            $user->email = $username . '@example.com';
            $user->password = \Illuminate\Support\Facades\Hash::make($newPassword);
            $user->save();
            
            // Assign role
            if (class_exists('\Spatie\Permission\Models\Role')) {
                $role = \Spatie\Permission\Models\Role::where('name', 'superAdmin')->first();
                if (!$role) {
                    $role = \Spatie\Permission\Models\Role::create(['name' => 'superAdmin']);
                }
                $user->assignRole('superAdmin');
            }
            
            echo "✅ New superAdmin user created:\n";
            echo "   Username: $username\n";
            echo "   Email: {$user->email}\n";
            echo "   Password: $newPassword\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== CACHE CLEARING ===\n";
echo "Clearing application caches...\n";
try {
    \Illuminate\Support\Facades\Artisan::call('cache:clear');
    echo "✅ Cache cleared\n";
    
    \Illuminate\Support\Facades\Artisan::call('config:clear');
    echo "✅ Config cache cleared\n";
    
    \Illuminate\Support\Facades\Artisan::call('route:clear');
    echo "✅ Route cache cleared\n";
    
    \Illuminate\Support\Facades\Artisan::call('view:clear');
    echo "✅ View cache cleared\n";
} catch (\Exception $e) {
    echo "❌ Error clearing caches: " . $e->getMessage() . "\n";
}

echo "\n=== PASSWORD RESET COMPLETE ===\n";
echo "You can now login with:\n";
echo "Username: $username\n";
echo "Password: $newPassword\n";
echo "\nLogin URL: " . config('app.url') . "/login\n"; 