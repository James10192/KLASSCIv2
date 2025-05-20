<?php
// Script to fix user credentials by updating passwords

// Bootstrap the Laravel application
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Spatie\Permission\Models\Role;

echo "=== Fixing User Credentials ===\n\n";

// Create roles if they don't exist
$roles = ['superAdmin', 'secretaire', 'etudiant', 'teacher'];
foreach ($roles as $roleName) {
    $role = Role::where('name', $roleName)->first();
    if (!$role) {
        Role::create(['name' => $roleName]);
        echo "Created missing role: $roleName\n";
    }
}

// Define our user templates
$userTemplates = [
    [
        'username' => 'superadmin',
        'email' => 'superadmin@esbtp.ci',
        'name' => 'Super Admin',
        'first_name' => 'Super',
        'last_name' => 'Admin',
        'password' => 'password123',
        'role' => 'superAdmin'
    ],
    [
        'username' => 'secretaire',
        'email' => 'secretaire@esbtp.ci',
        'name' => 'Secretaire Test',
        'first_name' => 'Secretaire',
        'last_name' => 'Test',
        'password' => 'password123',
        'role' => 'secretaire'
    ],
    [
        'username' => 'etudiant',
        'email' => 'etudiant@esbtp.ci',
        'name' => 'Etudiant Test',
        'first_name' => 'Etudiant',
        'last_name' => 'Test',
        'password' => 'password123',
        'role' => 'etudiant'
    ],
    [
        'username' => 'enseignant',
        'email' => 'enseignant@esbtp.ci',
        'name' => 'Enseignant Test',
        'first_name' => 'Enseignant',
        'last_name' => 'Test',
        'password' => 'password123',
        'role' => 'teacher'
    ]
];

// Function to check if username exists for a different user
function isUsernameTaken($username, $exceptUserId = null) {
    $query = User::where('username', $username);
    
    if ($exceptUserId) {
        $query->where('id', '!=', $exceptUserId);
    }
    
    return $query->exists();
}

// Function to update or create users
function updateOrCreateUser($template) {
    // First, check if this email exists
    $user = User::where('email', $template['email'])->first();
    
    if ($user) {
        echo "Updating user with email {$template['email']}...\n";
        
        // Check if username is taken by someone else
        if (isUsernameTaken($template['username'], $user->id)) {
            echo "  ⚠️ Username '{$template['username']}' is already taken by another user!\n";
            
            // If it's already taken, we'll make a unique one
            $originalUsername = $template['username'];
            $counter = 1;
            
            while (isUsernameTaken($template['username'], $user->id)) {
                $template['username'] = $originalUsername . '_' . $counter;
                $counter++;
            }
            
            echo "  ℹ️ Changed username to '{$template['username']}'\n";
        }
        
        // Update all fields
        $user->username = $template['username'];
        $user->name = $template['name'];
        $user->first_name = $template['first_name'];
        $user->last_name = $template['last_name'];
        $user->password = Hash::make($template['password']);
        
        try {
            $user->save();
            echo "  ✅ User updated successfully\n";
        } catch (\Exception $e) {
            echo "  ❌ Error updating user: " . $e->getMessage() . "\n";
        }
    } else {
        echo "Creating new user {$template['email']}...\n";
        
        // Check if username is taken
        if (isUsernameTaken($template['username'])) {
            echo "  ⚠️ Username '{$template['username']}' is already taken!\n";
            
            // If it's already taken, we'll make a unique one
            $originalUsername = $template['username'];
            $counter = 1;
            
            while (isUsernameTaken($template['username'])) {
                $template['username'] = $originalUsername . '_' . $counter;
                $counter++;
            }
            
            echo "  ℹ️ Using unique username '{$template['username']}'\n";
        }
        
        // Create new user
        try {
            $user = new User();
            $user->username = $template['username'];
            $user->email = $template['email'];
            $user->name = $template['name'];
            $user->first_name = $template['first_name'];
            $user->last_name = $template['last_name'];
            $user->password = Hash::make($template['password']);
            $user->email_verified_at = now();
            $user->save();
            
            echo "  ✅ User created successfully\n";
        } catch (\Exception $e) {
            echo "  ❌ Error creating user: " . $e->getMessage() . "\n";
            return null;
        }
    }
    
    // Assign role
    if (!$user) {
        echo "  ❌ Cannot assign role: user object is null\n";
        return null;
    }
    
    if ($user->hasRole($template['role'])) {
        echo "  ✓ User already has role '{$template['role']}'\n";
    } else {
        try {
            $user->syncRoles([$template['role']]);
            echo "  ✅ Assigned role '{$template['role']}'\n";
        } catch (\Exception $e) {
            echo "  ❌ Error assigning role: " . $e->getMessage() . "\n";
        }
    }
    
    // Verify password hash
    if (Hash::check($template['password'], $user->password)) {
        echo "  ✅ Password verified successfully\n";
    } else {
        echo "  ❌ Password verification failed!\n";
    }
    
    // Return the updated template with possibly modified username
    $template['actualUsername'] = $user->username;
    return [
        'user' => $user,
        'template' => $template
    ];
}

// Process each user template
$results = [];
foreach ($userTemplates as $template) {
    $result = updateOrCreateUser($template);
    if ($result && $result['user']) {
        $results[] = $result;
    }
    echo "\n";
}

// Verification
echo "=== Verification ===\n\n";

foreach ($results as $result) {
    $user = $result['user'];
    $template = $result['template'];
    
    echo "User: {$user->name} (ID: {$user->id})\n";
    echo "  Username: {$user->username}\n";
    echo "  Email: {$user->email}\n";
    
    // Verify password
    if (Hash::check($template['password'], $user->password)) {
        echo "  Password: ✅ CORRECT ('{$template['password']}')\n";
    } else {
        echo "  Password: ❌ INCORRECT\n";
    }
    
    // Verify role
    if ($user->hasRole($template['role'])) {
        echo "  Role: ✅ Has role '{$template['role']}'\n";
    } else {
        echo "  Role: ❌ Missing role '{$template['role']}'\n";
    }
    
    echo "\n";
}

// Login instructions
echo "=== Login Instructions ===\n\n";
echo "You can now log in with the following credentials:\n\n";

foreach ($results as $result) {
    $user = $result['user'];
    $template = $result['template'];
    
    echo "{$template['role']}:\n";
    echo "  Username: {$user->username}\n";
    echo "  Password: {$template['password']}\n\n";
}

echo "Make sure to use the username (not email) when logging in.\n"; 