<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Get the superAdmin user
$user = App\Models\User::where('role', 'superAdmin')->first();
if (!$user) {
    echo "No superAdmin user found" . PHP_EOL;
    exit;
}

echo "Testing with user ID: " . $user->id . PHP_EOL;
echo "User roles: " . implode(', ', $user->getRoleNames()->toArray()) . PHP_EOL;
echo "User has 'edit_timetables' permission: " . ($user->hasPermissionTo('edit_timetables') ? 'Yes' : 'No') . PHP_EOL;

// Check if the emploi_temp with ID 1 exists
$emploiTemp = App\Models\ESBTPEmploiTemps::find(1);
echo "Emploi temps with ID 1 exists: " . ($emploiTemp ? 'Yes' : 'No') . PHP_EOL;

if ($emploiTemp) {
    echo "Emploi temps details:" . PHP_EOL;
    echo "- Title: " . $emploiTemp->titre . PHP_EOL;
    echo "- Class ID: " . $emploiTemp->classe_id . PHP_EOL;
    echo "- Created by: " . $emploiTemp->created_by . PHP_EOL;
}

echo "Done!" . PHP_EOL;
