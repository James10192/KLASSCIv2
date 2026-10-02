<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Les deux seuils au-dessus desquels une action laisse une trace. Les clés
 * sont écrites en toutes lettres : une migration décrit un état figé.
 */
return new class extends Migration
{
    private const REGLAGES = [
        'exploitation.traces_lentes.seuil_ms' => [
            'value' => '1000', 'sort_order' => 190,
            'description' => 'Durée, en millisecondes, au-delà de laquelle une page ou un travail est noté comme lent.',
        ],
        'exploitation.traces_lentes.seuil_requetes' => [
            'value' => '100', 'sort_order' => 191,
            'description' => 'Nombre de requêtes à la base au-delà duquel une page ou un travail est noté comme lent.',
        ],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $createur = DB::table('users')->min('id');
        foreach (self::REGLAGES as $cle => $r) {
            if (DB::table('settings')->where('key', $cle)->exists()) {
                continue;
            }
            DB::table('settings')->insert([
                'key' => $cle,
                'value' => $r['value'],
                'type' => 'integer',
                'group' => 'general',
                'category' => 'general',
                'default_value' => $r['value'],
                'description' => $r['description'],
                'is_required' => 0,
                'validation_rules' => null,
                'is_active' => 1,
                'sort_order' => $r['sort_order'],
                'created_by' => $createur,
                'updated_by' => $createur,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', array_keys(self::REGLAGES))->delete();
    }
};
