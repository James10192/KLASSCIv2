<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('settings')->where('key', 'lmd_bulletin_bottom_width_percent')->exists()) {
            return;
        }

        DB::table('settings')->insert([
            'key' => 'lmd_bulletin_bottom_width_percent',
            'value' => '104',
            'type' => 'float',
            'group' => 'bulletin',
            'category' => 'bulletin',
            'description' => 'Largeur du pied de page du bulletin LMD en pourcentage',
            'is_required' => false,
            'default_value' => '104',
            'validation_rules' => json_encode(['nullable', 'numeric', 'min:90', 'max:108']),
            'is_active' => true,
            'sort_order' => 334,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Les réglages sont des données tenant : ne pas supprimer une valeur
        // éventuellement personnalisée lors d'un rollback applicatif.
    }
};
