<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\User;
use Spatie\Permission\Models\Role;

// Ensure the superAdmin role exists
$role = Role::firstOrCreate(['name' => 'superAdmin']);

// Get all users with role column = superAdmin and assign them the role via Spatie
$users = User::where('role', 'superAdmin')->get();
foreach ($users as $user) {
    $user->assignRole('superAdmin');
    echo "Assigned superAdmin role to user {$user->id}\n";
}

echo "Done!\n";
