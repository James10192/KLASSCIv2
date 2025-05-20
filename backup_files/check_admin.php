<?php

// Script minimal pour vérifier les administrateurs
$superAdmins = DB::table('users')->where('role', 'superAdmin')->get();
echo "Super Admins trouvés: " . $superAdmins->count() . "\n";
foreach ($superAdmins as $admin) {
    echo "ID: {$admin->id}, Nom: {$admin->name}, Email: {$admin->email}\n";
}

$ruben = DB::table('users')->where('email', 'ruben@gmail.com')->first();
if ($ruben) {
    echo "\nRuben: ID {$ruben->id}, Rôle {$ruben->role}\n";
} 