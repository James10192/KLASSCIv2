<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seed les 2 settings réconciliation sur tenants existants.
 * Idempotent : skip si déjà présent.
 */
return new class extends Migration
{
    public function up(): void
    {
        // created_by/updated_by : 1er user existant (null si DB vide, ex: klassci_testing).
        // La FK settings.created_by est ON DELETE SET NULL → null accepté.
        $creatorId = DB::table('users')->min('id');

        $rows = [
            [
                'key' => 'comptabilite.reconciliation.frequency',
                'value' => 'daily',
                'type' => 'string',
                'group' => 'comptabilite',
                'category' => 'comptabilite',
                'description' => 'Fréquence par défaut des sessions de réconciliation caisse (daily / weekly / monthly).',
                'is_required' => 1,
                'default_value' => 'daily',
                'validation_rules' => json_encode(['required', 'in:daily,weekly,monthly']),
                'sort_order' => 10,
                'is_active' => 1,
                'created_by' => $creatorId,
                'updated_by' => $creatorId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'comptabilite.reconciliation.require_separation_of_duties',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'comptabilite',
                'category' => 'comptabilite',
                'description' => 'Si activé, l\'approbateur d\'une session doit être différent de l\'ouvreur (séparation des devoirs OHADA).',
                'is_required' => 1,
                'default_value' => '1',
                'validation_rules' => json_encode(['required', 'boolean']),
                'sort_order' => 11,
                'is_active' => 1,
                'created_by' => $creatorId,
                'updated_by' => $creatorId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        $existing = DB::table('settings')->whereIn('key', array_column($rows, 'key'))->pluck('key')->all();
        $toInsert = array_filter($rows, fn ($r) => !in_array($r['key'], $existing, true));
        if (!empty($toInsert)) {
            DB::table('settings')->insert(array_values($toInsert));
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'comptabilite.reconciliation.frequency',
            'comptabilite.reconciliation.require_separation_of_duties',
        ])->delete();
    }
};
