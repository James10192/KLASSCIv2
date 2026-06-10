<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * PR5 réconciliation — 5 settings tenant idempotents :
 * - seuil tolérance écart (default 100 FCFA) : sous ce seuil, pas de discrepancy auto
 * - 4 URLs portails merchant Mobile Money (nullable, configurable per école)
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        // 1er user existant (null si DB vide, ex: klassci_testing). FK ON DELETE SET NULL → null OK.
        $creatorId = DB::table('users')->min('id');
        $rows = [
            [
                'key' => 'comptabilite.reconciliation.ecart_tolerance',
                'value' => '100',
                'type' => 'integer',
                'group' => 'comptabilite',
                'category' => 'comptabilite',
                'description' => 'Seuil de tolérance (en FCFA) sous lequel un écart de réconciliation n\'est pas considéré comme une discrepancy. Default 100 FCFA pour absorber les arrondis.',
                'is_required' => 1,
                'default_value' => '100',
                'validation_rules' => json_encode(['required', 'integer', 'min:0']),
                'sort_order' => 12,
            ],
            [
                'key' => 'comptabilite.reconciliation.portal_url_orange_money',
                'value' => '',
                'type' => 'string',
                'group' => 'comptabilite',
                'category' => 'comptabilite',
                'description' => 'URL du portail merchant Orange Money pour cette école. Affichée en hint dans l\'UI réconciliation pour pointer manuellement.',
                'is_required' => 0,
                'default_value' => '',
                'validation_rules' => json_encode(['nullable', 'url', 'max:255']),
                'sort_order' => 13,
            ],
            [
                'key' => 'comptabilite.reconciliation.portal_url_mtn_money',
                'value' => '',
                'type' => 'string',
                'group' => 'comptabilite',
                'category' => 'comptabilite',
                'description' => 'URL du portail merchant MTN MoMo pour cette école.',
                'is_required' => 0,
                'default_value' => '',
                'validation_rules' => json_encode(['nullable', 'url', 'max:255']),
                'sort_order' => 14,
            ],
            [
                'key' => 'comptabilite.reconciliation.portal_url_moov_money',
                'value' => '',
                'type' => 'string',
                'group' => 'comptabilite',
                'category' => 'comptabilite',
                'description' => 'URL du portail merchant Moov Money pour cette école.',
                'is_required' => 0,
                'default_value' => '',
                'validation_rules' => json_encode(['nullable', 'url', 'max:255']),
                'sort_order' => 15,
            ],
            [
                'key' => 'comptabilite.reconciliation.portal_url_wave',
                'value' => '',
                'type' => 'string',
                'group' => 'comptabilite',
                'category' => 'comptabilite',
                'description' => 'URL du portail Wave Business pour cette école.',
                'is_required' => 0,
                'default_value' => '',
                'validation_rules' => json_encode(['nullable', 'url', 'max:255']),
                'sort_order' => 16,
            ],
        ];

        $existing = DB::table('settings')->whereIn('key', array_column($rows, 'key'))->pluck('key')->all();
        $toInsert = array_values(array_filter($rows, fn ($r) => !in_array($r['key'], $existing, true)));

        if (!empty($toInsert)) {
            $toInsert = array_map(fn ($r) => array_merge($r, [
                'is_active' => 1,
                'created_by' => $creatorId,
                'updated_by' => $creatorId,
                'created_at' => $now,
                'updated_at' => $now,
            ]), $toInsert);
            DB::table('settings')->insert($toInsert);
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'comptabilite.reconciliation.ecart_tolerance',
            'comptabilite.reconciliation.portal_url_orange_money',
            'comptabilite.reconciliation.portal_url_mtn_money',
            'comptabilite.reconciliation.portal_url_moov_money',
            'comptabilite.reconciliation.portal_url_wave',
        ])->delete();
    }
};
