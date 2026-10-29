<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ajoute les 3 settings tenant pour le mapping SAARI (Sage Ligne 100) :
 * - saari_code_journal : code journal SAARI (default 'JV')
 * - saari_default_account : compte par défaut si pas de mapping (default vide)
 * - saari_account_mapping : mapping JSON cat → compte (default '{}')
 *
 * Idempotent : INSERT IGNORE — pas de ré-écriture si déjà présents.
 * À déployer sur chaque tenant via `klassci migrate <tenant>`.
 */
return new class extends Migration
{
    public function up(): void
    {
        $existing = collect(DB::table('settings')->whereIn('key', [
            'saari_code_journal',
            'saari_default_account',
            'saari_account_mapping',
        ])->pluck('key'))->all();

        $defaults = [
            [
                'key' => 'saari_code_journal',
                'value' => 'JV',
                'type' => 'string',
                'group' => 'comptabilite',
                'category' => 'comptabilite',
                'description' => 'Code journal SAARI pour exports compta (ex: JV, BK, CA)',
                'is_required' => false,
                'default_value' => 'JV',
                'validation_rules' => json_encode(['nullable', 'string', 'max:10']),
                'sort_order' => 1,
            ],
            [
                'key' => 'saari_default_account',
                'value' => '',
                'type' => 'string',
                'group' => 'comptabilite',
                'category' => 'comptabilite',
                'description' => 'Numéro de compte SAARI par défaut (utilisé si la catégorie de frais n\'a pas de mapping)',
                'is_required' => false,
                'default_value' => '',
                'validation_rules' => json_encode(['nullable', 'string', 'max:20']),
                'sort_order' => 2,
            ],
            [
                'key' => 'saari_account_mapping',
                'value' => '{}',
                'type' => 'string',
                'group' => 'comptabilite',
                'category' => 'comptabilite',
                'description' => 'Mapping JSON catégorie → compte SAARI. Format: {"<id_categorie>": "<compte>", "<mot_cle>": "<compte>"}',
                'is_required' => false,
                'default_value' => '{}',
                'validation_rules' => json_encode(['nullable', 'string']),
                'sort_order' => 3,
            ],
        ];

        foreach ($defaults as $row) {
            if (in_array($row['key'], $existing, true)) {
                continue;
            }
            DB::table('settings')->insert(array_merge($row, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'saari_code_journal',
            'saari_default_account',
            'saari_account_mapping',
        ])->delete();
    }
};
