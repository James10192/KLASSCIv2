<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Fix planification teacher ===\n";

// Trouver un enseignant
$teacher = App\Models\User::whereHas('roles', function($q) {
    $q->where('name', 'enseignant');
})->first();

if (!$teacher) {
    $teacher = App\Models\User::first();
}

if (!$teacher) {
    echo "❌ Aucun utilisateur trouvé\n";
    exit(1);
}

echo "👨‍🏫 Enseignant trouvé: {$teacher->name} (ID: {$teacher->id})\n";

// Mettre à jour la planification
$planification = App\Models\ESBTPPlanificationAcademique::first();
$planification->enseignant_principal_id = $teacher->id;
$planification->save();

echo "✅ Planification mise à jour avec enseignant ID: {$teacher->id}\n";