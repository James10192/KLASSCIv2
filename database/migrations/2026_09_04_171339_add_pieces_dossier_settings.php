<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Les cinq réglages du catalogue des pièces à fournir.
 *
 * Un réglage que personne ne peut changer n'est pas un réglage : c'est une
 * constante avec une clé. Ces valeurs sont donc inscrites dans la table
 * `settings`, ET la page de réglages (onglet Scolarité) porte le champ qui les
 * modifie. Les deux vont ensemble : la première livraison de ce lot n'avait
 * inscrit que les lignes, sans aucun champ nulle part, et une école qui voulait
 * lever le plafond de vingt n'avait d'autre recours que du SQL.
 *
 * Ce que chacune décide :
 *
 *  - le plafond du nombre d'exemplaires. Une école qui réclame quatre photos
 *    d'identité n'est pas une école qui en réclame quarante : le plafond arrête
 *    la faute de frappe. Mais où le placer est une décision d'établissement, pas
 *    une vérité de KLASSCI ;
 *  - la forme proposée par défaut au guichet (original, copie, ou indifférent) ;
 *  - l'échéance proposée par défaut (à l'inscription, ou avant la fin de
 *    l'année) ;
 *  - ce que l'école fait quand le dépôt d'un étudiant ne couvre plus ce qu'une
 *    inscription consomme ;
 *  - si une inscription annulée rend immédiatement ses exemplaires au dépôt.
 *
 * Les deuxième et troisième ne contraignent rien : elles pré-remplissent le
 * formulaire de création d'une pièce. Une école qui tolère la plupart des pièces
 * en cours d'année ne veut pas décocher la même case cinquante fois de suite.
 *
 * Les deux dernières ne produiront d'effet qu'au lot suivant, celui qui livre le
 * suivi pièce par pièce (docs/lot-2-pieces-a-reprendre.md). Elles sont écrites
 * ici parce que ce sont des DÉCISIONS D'ÉCOLE, et qu'une décision d'école ne se
 * découvre pas le jour du déploiement : l'écran les pose dès maintenant, et dit
 * en toutes lettres qu'elles attendent cette suite.
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
        [
            'key' => 'pieces_dossier.epuisement',
            'value' => 'signaler',
            'type' => 'string',
            'default_value' => 'signaler',
            'sort_order' => 173,
            'description' => "Que faire quand le depot d'un etudiant ne couvre plus ce qu'une inscription consomme. bloquer : l'inscription ne se valide pas tant que la piece n'est pas redeposee. signaler : le dossier est marque incomplet, sans rien empecher. silence : ne rien signaler. Defaut : signaler.",
        ],
        [
            'key' => 'pieces_dossier.restitution_annulation',
            'value' => '1',
            'type' => 'boolean',
            'default_value' => '1',
            'sort_order' => 174,
            'description' => "Une inscription annulee rend-elle au depot les exemplaires qu'elle consommait ? Oui : ils redeviennent disponibles immediatement. Non : ils restent retenus jusqu'a la fin de l'annee. Defaut : oui.",
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
