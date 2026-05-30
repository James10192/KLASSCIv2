<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $permissions = [
            'bts_tronc_commun.view' => "Voir le parcours BTS tronc commun",
            'bts_tronc_commun.orient' => "Orienter un étudiant BTS tronc commun",
            'bts_tronc_commun.manage_targets' => "Configurer les sorties BTS tronc commun",
            'bts_tronc_commun.view_history' => "Voir l'historique BTS tronc commun",
        ];

        foreach ($permissions as $name => $description) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $name,
                'guard_name' => 'web',
                'category' => 'Académique',
                'description' => $description,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('name', [
            'bts_tronc_commun.view',
            'bts_tronc_commun.orient',
            'bts_tronc_commun.manage_targets',
            'bts_tronc_commun.view_history',
        ])->delete();
    }
};
