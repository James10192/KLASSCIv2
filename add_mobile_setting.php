<?php

require_once 'vendor/autoload.php';
use App\Models\Setting;

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Ajout du paramètre school_mobile...\n";

try {
    $existing = Setting::where('key', 'school_mobile')->first();

    if ($existing) {
        echo "Le paramètre school_mobile existe déjà avec la valeur: " . $existing->value . "\n";
    } else {
        Setting::create([
            'key' => 'school_mobile',
            'value' => '07 07 79 84 85',
            'type' => 'string',
            'category' => 'establishment',
            'description' => 'Numero de telephone mobile/cellulaire',
            'is_required' => false,
            'is_active' => true,
            'sort_order' => 7
        ]);
        echo "✅ Paramètre school_mobile créé avec succès!\n";
    }
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}
