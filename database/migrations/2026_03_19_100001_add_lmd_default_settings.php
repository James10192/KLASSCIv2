<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Setting;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            // ── Crédits ──
            ['key' => 'lmd_credits_per_semester', 'value' => '30', 'type' => 'integer', 'group' => 'lmd', 'category' => 'credits', 'description' => 'Nombre de crédits CECT par semestre', 'default_value' => '30', 'sort_order' => 1],
            ['key' => 'lmd_credits_licence_total', 'value' => '180', 'type' => 'integer', 'group' => 'lmd', 'category' => 'credits', 'description' => 'Total crédits pour la Licence (6 semestres)', 'default_value' => '180', 'sort_order' => 2],
            ['key' => 'lmd_credits_master_total', 'value' => '120', 'type' => 'integer', 'group' => 'lmd', 'category' => 'credits', 'description' => 'Total crédits pour le Master (4 semestres)', 'default_value' => '120', 'sort_order' => 3],
            ['key' => 'lmd_credits_doctorat_total', 'value' => '180', 'type' => 'integer', 'group' => 'lmd', 'category' => 'credits', 'description' => 'Total crédits pour le Doctorat (6 semestres)', 'default_value' => '180', 'sort_order' => 4],

            // ── Validation & Compensation ──
            ['key' => 'lmd_validation_threshold', 'value' => '10', 'type' => 'integer', 'group' => 'lmd', 'category' => 'validation', 'description' => 'Seuil de validation UE (note /20)', 'default_value' => '10', 'sort_order' => 10],
            ['key' => 'lmd_compensation_inter_ue', 'value' => '1', 'type' => 'boolean', 'group' => 'lmd', 'category' => 'validation', 'description' => 'Compensation entre UE du même semestre (APC)', 'default_value' => '1', 'sort_order' => 11],
            ['key' => 'lmd_compensation_intra_ue', 'value' => '1', 'type' => 'boolean', 'group' => 'lmd', 'category' => 'validation', 'description' => 'Compensation entre ECUE dans la même UE', 'default_value' => '1', 'sort_order' => 12],
            ['key' => 'lmd_note_eliminatoire', 'value' => '0', 'type' => 'integer', 'group' => 'lmd', 'category' => 'validation', 'description' => 'Note éliminatoire (0 = pas de note éliminatoire, conforme UEMOA)', 'default_value' => '0', 'sort_order' => 13],

            // ── Évaluations ──
            ['key' => 'lmd_cc_weight', 'value' => '40', 'type' => 'integer', 'group' => 'lmd', 'category' => 'evaluations', 'description' => 'Pondération Contrôle Continu (%)', 'default_value' => '40', 'sort_order' => 20],
            ['key' => 'lmd_exam_weight', 'value' => '60', 'type' => 'integer', 'group' => 'lmd', 'category' => 'evaluations', 'description' => 'Pondération Examen Final (%)', 'default_value' => '60', 'sort_order' => 21],
            ['key' => 'lmd_rattrapage_scope', 'value' => 'ecue', 'type' => 'string', 'group' => 'lmd', 'category' => 'evaluations', 'description' => 'Portée du rattrapage : repasser les ECUE ratés ou toute l\'UE', 'default_value' => 'ecue', 'sort_order' => 22],

            // ── Mentions UE ──
            ['key' => 'lmd_mention_tb_threshold', 'value' => '16', 'type' => 'integer', 'group' => 'lmd', 'category' => 'mentions', 'description' => 'Seuil mention Très Bien (TB)', 'default_value' => '16', 'sort_order' => 30],
            ['key' => 'lmd_mention_b_threshold', 'value' => '14', 'type' => 'integer', 'group' => 'lmd', 'category' => 'mentions', 'description' => 'Seuil mention Bien (B)', 'default_value' => '14', 'sort_order' => 31],
            ['key' => 'lmd_mention_ab_threshold', 'value' => '12', 'type' => 'integer', 'group' => 'lmd', 'category' => 'mentions', 'description' => 'Seuil mention Assez Bien (AB)', 'default_value' => '12', 'sort_order' => 32],
            ['key' => 'lmd_mention_p_threshold', 'value' => '10', 'type' => 'integer', 'group' => 'lmd', 'category' => 'mentions', 'description' => 'Seuil mention Passable (P)', 'default_value' => '10', 'sort_order' => 33],

            // ── Délibération ──
            ['key' => 'lmd_deliberation_decisions', 'value' => '["Félicitations du jury","Tableau d\'honneur","Encouragement pour le travail fourni","Passage","Passage conditionnel","Ajourné(e)","Exclusion"]', 'type' => 'json', 'group' => 'lmd', 'category' => 'deliberation', 'description' => 'Liste des décisions possibles lors de la délibération', 'default_value' => '["Félicitations du jury","Tableau d\'honneur","Encouragement pour le travail fourni","Passage","Passage conditionnel","Ajourné(e)","Exclusion"]', 'sort_order' => 40],
        ];

        foreach ($settings as $s) {
            Setting::updateOrCreate(
                ['key' => $s['key']],
                array_merge($s, [
                    'is_active' => true,
                    'is_required' => false,
                ])
            );
        }
    }

    public function down(): void
    {
        Setting::where('group', 'lmd')->delete();
    }
};
