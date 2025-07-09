<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ajouter les nouvelles permissions comptabilité
        $permissions = [
            ['name' => 'comptabilite.dashboard.view', 'guard_name' => 'web'],
            ['name' => 'comptabilite.bons.approve', 'guard_name' => 'web'],
            ['name' => 'comptabilite.config.manage', 'guard_name' => 'web'],
            ['name' => 'comptabilite.reports.export', 'guard_name' => 'web'],
            ['name' => 'comptabilite.relances.send', 'guard_name' => 'web'],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $permission['name'],
                'guard_name' => $permission['guard_name'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer les permissions ajoutées
        $permissionNames = [
            'comptabilite.dashboard.view',
            'comptabilite.bons.approve',
            'comptabilite.config.manage',
            'comptabilite.reports.export',
            'comptabilite.relances.send',
        ];

        DB::table('permissions')->whereIn('name', $permissionNames)->delete();
    }
};
