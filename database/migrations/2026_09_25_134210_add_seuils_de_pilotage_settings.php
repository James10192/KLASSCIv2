<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Sème les trois seuils des tableaux de bord de pilotage.
 *
 * L'écran de réglages ne met à jour que les lignes existantes : sans ce semis,
 * les champs s'afficheraient sans jamais être enregistrés. Les clés sont
 * écrites en toutes lettres : une migration décrit un état figé.
 */
return new class extends Migration
{
    private const REGLAGES = [
        'pilotage.relance_notes_apres_jours' => [
            'value' => '7',
            'description' => 'Nombre de jours après une évaluation au-delà duquel une note manquante apparaît dans « À relancer » du pilotage académique.',
            'sort_order' => 170,
        ],
        'pilotage.seuil_presence_pct' => [
            'value' => '75',
            'description' => 'Taux de présence (en %) en dessous duquel un étudiant est signalé dans le pilotage académique.',
            'sort_order' => 171,
        ],
        'personnel.paiement_attente_jours' => [
            'value' => '3',
            'description' => 'Nombre de jours au-delà duquel un paiement en attente de validation est signalé dans l’activité du personnel.',
            'sort_order' => 172,
        ],
    ];

    public function up(): void
    {
        $createur = DB::table('users')->min('id');

        foreach (self::REGLAGES as $cle => $reglage) {
            if (DB::table('settings')->where('key', $cle)->exists()) {
                continue;
            }

            DB::table('settings')->insert([
                'key' => $cle,
                'value' => $reglage['value'],
                'type' => 'integer',
                'group' => 'scolarite',
                'category' => 'scolarite',
                'default_value' => $reglage['value'],
                'description' => $reglage['description'],
                'is_required' => 0,
                'validation_rules' => null,
                'is_active' => 1,
                'sort_order' => $reglage['sort_order'],
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
