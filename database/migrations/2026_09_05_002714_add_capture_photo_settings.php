<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Les trois réglages de la prise de vue au téléphone.
 *
 * Aucun n'a de réponse unique valable pour six écoles :
 *
 *  - une école dont tout le personnel travaille sur des postes équipés de
 *    webcams n'a aucun usage du détour par le téléphone, et l'affichage d'un
 *    code QR de plus ne fait qu'encombrer l'écran ;
 *  - la durée de vie du lien est un arbitrage entre le confort — laisser le
 *    temps de sortir son téléphone, déverrouiller, scanner, cadrer — et le
 *    risque d'un code resté affiché sur un écran sans surveillance ;
 *  - demander confirmation avant de remplacer une photo existante protège
 *    contre l'écrasement distrait, mais ralentit une école qui refait
 *    systématiquement les photos à chaque rentrée.
 */
return new class extends Migration
{
    private const REGLAGES = [
        [
            'key' => 'capture_photo.telephone_actif',
            'value' => '1',
            'type' => 'boolean',
            'default_value' => '1',
            'sort_order' => 180,
            'description' => "Proposer la prise de vue par telephone (code QR a scanner) en plus du televersement et de la webcam. Utile quand le poste du guichet n'a pas de camera, ou en a une mauvaise. Defaut : oui.",
        ],
        [
            'key' => 'capture_photo.duree_minutes',
            'value' => '10',
            'type' => 'integer',
            'default_value' => '10',
            'sort_order' => 181,
            'description' => "Duree de validite du lien affiche dans le code QR, en minutes. Passe ce delai, le lien ne s'ouvre plus et le guichet doit en afficher un nouveau. Court : moins de risque qu'un code reste affiche sans surveillance. Long : plus de confort pour sortir son telephone et cadrer. Defaut : 10.",
        ],
        [
            'key' => 'capture_photo.confirmer_remplacement',
            'value' => '1',
            'type' => 'boolean',
            'default_value' => '1',
            'sort_order' => 182,
            'description' => "Demander confirmation avant de remplacer la photo d'un etudiant qui en a deja une — le cas de la reinscription. Non : la nouvelle photo remplace l'ancienne sans question. Defaut : oui.",
        ],
    ];

    public function up(): void
    {
        // settings.created_by porte une cle etrangere vers users en ON DELETE
        // SET NULL : coder « 1 » en dur casserait la migration sur une base
        // fraiche, ou aucun utilisateur n'existe encore.
        $createur = DB::table('users')->min('id');
        $maintenant = now();

        foreach (self::REGLAGES as $reglage) {
            // Idempotent : une instance qui a deja le reglage garde SA valeur.
            if (DB::table('settings')->where('key', $reglage['key'])->exists()) {
                continue;
            }

            DB::table('settings')->insert([
                'key' => $reglage['key'],
                'value' => $reglage['value'],
                'type' => $reglage['type'],
                'group' => 'scolarite',
                'category' => 'scolarite',
                'default_value' => $reglage['default_value'],
                'description' => $reglage['description'],
                'is_required' => 0,
                'validation_rules' => null,
                'is_active' => 1,
                'sort_order' => $reglage['sort_order'],
                'created_by' => $createur,
                'updated_by' => $createur,
                'created_at' => $maintenant,
                'updated_at' => $maintenant,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')
            ->whereIn('key', array_column(self::REGLAGES, 'key'))
            ->delete();
    }
};
