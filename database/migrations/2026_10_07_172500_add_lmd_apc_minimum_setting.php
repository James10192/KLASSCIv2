<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('settings')->where('key', 'lmd_compensation_inter_ue_minimum')->exists()) {
            return;
        }

        DB::table('settings')->insert([
            'key' => 'lmd_compensation_inter_ue_minimum',
            'value' => '0',
            'type' => 'float',
            'group' => 'lmd',
            'category' => 'validation',
            'description' => 'Moyenne minimale d une UE pour autoriser son acquisition par compensation (APC)',
            'is_required' => false,
            'default_value' => '0',
            'validation_rules' => json_encode(['nullable', 'numeric', 'min:0', 'max:20']),
            'is_active' => true,
            'sort_order' => 12,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Réglage académique tenant : ne pas supprimer une valeur personnalisée
        // lors d'un rollback applicatif.
    }
};
