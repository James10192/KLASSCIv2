<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Les trois réglages du catalogue des pièces à fournir.
 *
 * Un réglage que personne ne peut changer n'est pas un réglage : c'est une
 * constante avec une clé. Ces trois valeurs sont donc inscrites dans la table
 * `settings`, où la page de réglages les rend visibles et modifiables, plutôt
 * que laissées en dur dans la validation et le formulaire.
 *
 * Ce que chacune décide :
 *
 *  - le plafond du nombre d'exemplaires. Une école qui réclame quatre photos
 *    d'identité n'est pas une école qui en réclame quarante : le plafond arrête
 *    la faute de frappe. Mais où le placer est une décision d'établissement, pas
 *    une vérité de KLASSCI ;
 *  - la forme proposée par défaut au guichet (original, copie, ou indifférent) ;
 *  - l'échéance proposée par défaut (à l'inscription, ou avant la fin de
 *    l'année).
 *
 * Les deux dernières ne contraignent rien : elles pré-remplissent le formulaire
 * de création d'une pièce. Une école qui tolère la plupart des pièces en cours
 * d'année ne veut pas décocher la même case cinquante fois de suite.
 */
return new class extends Migration
{
    private const REGLAGES = [
        [
            'key' => 'pieces_dossier.exemplaires_max',
            'value' => '20',
            'type' => 'integer',
            'default_value' => '20',
            'sort_order' => 170,
            'description' => "Nombre maximal d'exemplaires qu'une piece du catalogue peut reclamer. Au-dela, la saisie est refusee : c'est un garde-fou contre la faute de frappe, pas une limite technique.",
        ],
        [
            'key' => 'pieces_dossier.forme_defaut',
            'value' => 'copie',
            'type' => 'string',
            'default_value' => 'copie',
            'sort_order' => 171,
            'description' => "Forme proposee par defaut a la creation d'une piece du catalogue. Valeurs acceptees : original, copie, indifferent. Une valeur non reconnue est ramenee a « copie ».",
        ],
        [
            'key' => 'pieces_dossier.echeance_defaut',
            'value' => 'inscription',
            'type' => 'string',
            'default_value' => 'inscription',
            'sort_order' => 172,
            'description' => "Echeance proposee par defaut a la creation d'une piece du catalogue. Valeurs acceptees : inscription, avant_fin_annee. Une valeur non reconnue est ramenee a « inscription ».",
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
            // Reecrire ici ecraserait le choix de l'ecole a chaque deploiement.
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
