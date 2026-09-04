<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Porte le parcours sur le lien UE <-> element constitutif.
 *
 * Le code d'une UE est unique dans l'ecole : l'unite est REELLEMENT partagee
 * entre parcours, jamais dupliquee. Ce qui differe d'un parcours a l'autre,
 * c'est la LISTE DES ELEMENTS qu'on y suit. Cette liste ne pouvait pas
 * s'exprimer : le pivot ne connaissait que le couple (unite, matiere), donc
 * les deux parcours voyaient forcement le meme sac.
 *
 * Le partage vit donc dans le pivot, et NULLE PART AILLEURS. En particulier
 * `esbtp_matieres.unite_enseignement_id` reste intouchee : elle sert de
 * discriminateur BTS/LMD dans une vingtaine d'ecrans en service, et la vider
 * casserait le BTS de deux ecoles de plus de deux mille inscrits.
 *
 * Zero vaut « commun a tous les parcours de l'unite », et non « inconnu ».
 * Une colonne nullable aurait paru plus naturelle, mais l'unicite d'un triplet
 * dont une colonne est nulle n'est pas garantie de la meme facon selon le
 * moteur : deux lignes « communes » identiques passeraient, et l'element
 * compterait deux fois dans la moyenne de l'unite. Avec le sentinelle 0,
 * un UNIQUE ordinaire suffit et se comporte pareil partout. C'est aussi
 * pourquoi il n'y a pas de cle etrangere ici : elle refuserait le 0.
 *
 * Emprunte au lot « ecritures » pour que les lectures scopees tiennent debout
 * sur cette branche. Volontairement idempotente : si la meme colonne arrive
 * par l'autre lot, cette migration ne fait rien.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('esbtp_ue_matiere', 'parcours_id')) {
            return;
        }

        Schema::table('esbtp_ue_matiere', function (Blueprint $table) {
            $table->unsignedBigInteger('parcours_id')
                ->default(0)
                ->after('matiere_id')
                ->comment('0 = commun a tous les parcours de l\'UE ; sinon reserve a ce parcours');
        });

        // La nouvelle unicite est posee AVANT que l'ancienne ne tombe : elle
        // commence par la meme colonne, elle couvre donc la cle etrangere sur
        // `unite_enseignement_id` sans interruption. Dans l'autre ordre, MySQL
        // refuse la suppression (errno 150) faute d'index restant.
        Schema::table('esbtp_ue_matiere', function (Blueprint $table) {
            $table->unique(
                ['unite_enseignement_id', 'matiere_id', 'parcours_id'],
                'ue_matiere_parcours_unique'
            );
        });

        Schema::table('esbtp_ue_matiere', function (Blueprint $table) {
            $table->dropUnique('ue_matiere_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('esbtp_ue_matiere', 'parcours_id')) {
            return;
        }

        // Le retour arriere n'est possible que si aucun element n'a ete reserve
        // a un parcours : sinon l'ancienne unicite (unite, matiere) refuserait
        // les lignes, et les faire tomber silencieusement retirerait des
        // elements de maquettes sans que personne ne l'ait demande.
        $reserves = \Illuminate\Support\Facades\DB::table('esbtp_ue_matiere')
            ->where('parcours_id', '!=', 0)
            ->count();

        if ($reserves > 0) {
            throw new \RuntimeException(
                "Retour arriere impossible : {$reserves} element(s) constitutif(s) sont reserves a un parcours. "
                . "Ramenez-les au commun (parcours_id = 0) avant de rejouer cette migration a l'envers."
            );
        }

        Schema::table('esbtp_ue_matiere', function (Blueprint $table) {
            $table->unique(['unite_enseignement_id', 'matiere_id'], 'ue_matiere_unique');
        });

        Schema::table('esbtp_ue_matiere', function (Blueprint $table) {
            $table->dropUnique('ue_matiere_parcours_unique');
            $table->dropColumn('parcours_id');
        });
    }
};
