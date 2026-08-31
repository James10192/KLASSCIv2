<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Le schema interdisait l'etat que tout le code attend.
 *
 * Deux colonnes de esbtp_paiements sont declarees NOT NULL sans defaut alors que
 * l'application les traite comme facultatives. L'inventaire du reste de la table
 * est sain : inscription_id, etudiant_id, annee_universitaire_id, montant,
 * date_paiement, mode_paiement et numero_recu sont bien obligatoires pour un
 * paiement, et le restent.
 *
 * `esbtp_paiements.type_paiement` est declaree NOT NULL sans defaut depuis la
 * creation de la table. Or la lecture, elle, tient NULL pour un etat normal :
 * on compte huit endroits qui ecrivent
 *
 *     ->where('type_paiement', '!=', 'reliquat')->orWhereNull('type_paiement')
 *
 * — controleurs de paiements, d'inscriptions, de suivi, commandes de
 * verification. Le `orWhereNull` n'est pas defensif, il est structurel : la
 * colonne ne sert qu'a marquer les reliquats, et seuls deux endroits y ecrivent
 * une valeur reelle (« inscription » et « reliquat »). Tous les autres
 * encaissements la laissent vide.
 *
 * Cette contradiction ne se voit qu'a l'insertion, et elle est fatale :
 *
 *     SQLSTATE[HY000] 1364 Field 'type_paiement' doesn't have a default value
 *
 * Elle figure au journal de production de presentation, horodatee du
 * 2026-08-31 01:42:08, et elle fait echouer quatre tests de AvoirPaiementTest
 * plus un de PaiementCriticalFlowRegressionTest — echecs anterieurs a tout le
 * travail de cette session, verifies a l'identique sur le commit de base.
 *
 * On aligne donc le schema sur ce que le code dit depuis toujours, plutot que
 * d'aller renseigner la colonne aux quinze endroits qui creent un paiement : ce
 * serait imposer une valeur a un champ dont la vacuite EST le sens — « ce
 * paiement n'est pas un reliquat ».
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('esbtp_paiements') || ! Schema::hasColumn('esbtp_paiements', 'type_paiement')) {
            return;
        }

        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        // MODIFY brut plutot que ->nullable()->change() : le second relit la
        // definition existante et peut en perdre des attributs selon la version
        // de doctrine/dbal presente. Ici on ecrit exactement la colonne voulue.
        DB::statement('ALTER TABLE `esbtp_paiements` MODIFY `type_paiement` VARCHAR(100) NULL DEFAULT NULL');

        // `motif` : meme diagnostic, meme correctif.
        //
        // Les vues le lisent systematiquement avec un repli — « fraisCategory->name
        // ?? motif », « motif ?? type_paiement ?? 'Paiement' » — et paiements/show
        // l'entoure d'un @if. Un champ qu'on affiche sous condition n'est pas un
        // champ obligatoire. Les validations `required` sur « motif » qu'on trouve
        // ailleurs portent sur d'AUTRES formulaires — annulation d'inscription,
        // emission d'avoir, resolution d'ecart — ou le motif justifie une action,
        // et non sur le paiement lui-meme.
        DB::statement('ALTER TABLE `esbtp_paiements` MODIFY `motif` VARCHAR(191) NULL DEFAULT NULL');
    }

    /**
     * Le retour arriere n'est possible que si aucune ligne ne porte NULL.
     *
     * Repasser la colonne en NOT NULL alors que des paiements l'ont laissee vide
     * echouerait, ou pire, ecraserait ces valeurs par une chaine vide selon le
     * mode SQL du serveur. On ne defait donc que si l'etat le permet, et on le
     * dit dans le journal sinon.
     */
    public function down(): void
    {
        if (! Schema::hasTable('esbtp_paiements') || DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $vides = DB::table('esbtp_paiements')
            ->whereNull('type_paiement')
            ->orWhereNull('motif')
            ->count();

        if ($vides > 0) {
            \Illuminate\Support\Facades\Log::warning(
                '[migration] colonnes laissees nullables : des paiements portent NULL',
                ['lignes' => $vides]
            );

            return;
        }

        DB::statement('ALTER TABLE `esbtp_paiements` MODIFY `type_paiement` VARCHAR(100) NOT NULL');
        DB::statement('ALTER TABLE `esbtp_paiements` MODIFY `motif` VARCHAR(191) NOT NULL');
    }
};
