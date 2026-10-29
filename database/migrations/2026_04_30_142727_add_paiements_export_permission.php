<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Lot 15 — Ajoute la permission paiements.export pour l'export
 * détaillé des états financiers (et paiements.view_own pour le pattern
 * d'ownership des caissiers).
 *
 * Ces permissions sont créées via firstOrCreate-style logic (insertOrIgnore)
 * pour pouvoir coexister avec un éventuel registry config/permissions.php
 * (Lot 13) sans conflit.
 */
return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            ['name' => 'paiements.export', 'guard_name' => 'web'],
            ['name' => 'paiements.view_own', 'guard_name' => 'web'],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $permission['name'],
                'guard_name' => $permission['guard_name'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Donner paiements.export aux rôles superAdmin et comptable
        // (caissier l'aura via paiements.view_own + on l'attribuera via UI)
        $exportPermissionId = DB::table('permissions')
            ->where('name', 'paiements.export')
            ->where('guard_name', 'web')
            ->value('id');

        if ($exportPermissionId) {
            $roleNames = ['superAdmin', 'comptable', 'caissier'];
            $roleIds = DB::table('roles')
                ->whereIn('name', $roleNames)
                ->where('guard_name', 'web')
                ->pluck('id');

            foreach ($roleIds as $roleId) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $exportPermissionId,
                    'role_id' => $roleId,
                ]);
            }
        }
    }

    public function down(): void
    {
        $names = ['paiements.export', 'paiements.view_own'];

        $ids = DB::table('permissions')
            ->whereIn('name', $names)
            ->where('guard_name', 'web')
            ->pluck('id');

        if ($ids->isNotEmpty()) {
            DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
            DB::table('permissions')->whereIn('id', $ids)->delete();
        }
    }
};
