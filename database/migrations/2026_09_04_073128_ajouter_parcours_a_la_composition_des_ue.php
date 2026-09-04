<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Donne une maquette a chaque ligne de composition d'une unite d'enseignement.
 *
 * Une UE est REELLEMENT partagee entre parcours : son code est unique dans
 * l'ecole, on ne la duplique pas. Mais sa composition, elle, peut differer d'une
 * maquette a l'autre — Batiment et Travaux Publics n'ont pas exactement les
 * memes elements constitutifs sous la meme unite. Jusqu'ici le pivot
 * `esbtp_ue_matiere` ne portait aucune notion de parcours : les deux maquettes
 * lisaient le meme sac d'elements, et l'import a du renommer cinq ECUE a la main
 * pour eviter l'ecrasement.
 *
 * Pourquoi `parcours_id` NOT NULL avec 0 plutot que NULL :
 *  - une contrainte d'unicite ordinaire ignore les lignes ou une colonne est
 *    NULL (deux NULL ne sont jamais « egaux »). Avec un NULL pour « commun », la
 *    base laisserait passer deux lignes communes identiques ;
 *  - la parade habituelle est une colonne generee, qu'AUCUNE migration de ce
 *    depot n'utilise et dont le support depend d'une version de moteur que l'on
 *    ne connait pas sur les six instances ;
 *  - avec 0, l'unicite (unite, matiere, parcours) est un index ordinaire, valable
 *    partout. Le prix est l'absence de cle etrangere sur cette colonne : aucune
 *    ligne d'`esbtp_lmd_parcours` ne porte l'identifiant 0.
 *
 * Les lignes existantes prennent 0 : elles restent communes a toutes les
 * maquettes, c'est-a-dire exactement ce qu'elles etaient avant cette migration.
 *
 * `esbtp_lmd_parcours_ue.credit` accompagne le meme besoin cote unite : le
 * nombre de credits d'une UE peut differer d'une maquette a l'autre. NULL signifie
 * « pas de valeur propre a cette maquette, on retient le credit de l'UE » —
 * c'est-a-dire le comportement actuel pour toutes les lignes deja en base.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('esbtp_ue_matiere', 'parcours_id')) {
            Schema::table('esbtp_ue_matiere', function (Blueprint $table) {
                $table->unsignedBigInteger('parcours_id')->default(0)->after('matiere_id');
            });

            Schema::table('esbtp_ue_matiere', function (Blueprint $table) {
                // Le nouvel index est cree AVANT la suppression de l'ancien : il
                // commence par `unite_enseignement_id`, il peut donc servir la
                // cle etrangere que l'ancien index couvrait peut-etre seul. Sans
                // cela, le moteur refuse la suppression (errno 150).
                $table->unique(
                    ['unite_enseignement_id', 'matiere_id', 'parcours_id'],
                    'ue_matiere_parcours_unique'
                );
            });

            Schema::table('esbtp_ue_matiere', function (Blueprint $table) {
                $table->dropUnique('ue_matiere_unique');
            });
        }

        if (! Schema::hasColumn('esbtp_lmd_parcours_ue', 'credit')) {
            Schema::table('esbtp_lmd_parcours_ue', function (Blueprint $table) {
                $table->unsignedSmallInteger('credit')->nullable()->after('semestre');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('esbtp_ue_matiere', 'parcours_id')) {
            // On revient a l'unicite (unite, matiere) : elle n'est tenable que si
            // aucun couple n'a ete eclate en plusieurs maquettes. Les lignes
            // reservees sont donc ramenees au commun, puis dedoublonnees.
            $this->ramenerToutesLesLignesAuCommun();

            Schema::table('esbtp_ue_matiere', function (Blueprint $table) {
                $table->unique(['unite_enseignement_id', 'matiere_id'], 'ue_matiere_unique');
            });

            Schema::table('esbtp_ue_matiere', function (Blueprint $table) {
                $table->dropUnique('ue_matiere_parcours_unique');
                $table->dropColumn('parcours_id');
            });
        }

        if (Schema::hasColumn('esbtp_lmd_parcours_ue', 'credit')) {
            Schema::table('esbtp_lmd_parcours_ue', function (Blueprint $table) {
                $table->dropColumn('credit');
            });
        }
    }

    /**
     * Supprime les doublons (unite, matiere) en gardant la ligne la plus ancienne,
     * puis remet toutes les lignes restantes en commun.
     */
    private function ramenerToutesLesLignesAuCommun(): void
    {
        $vus = [];
        $aSupprimer = [];

        foreach (\Illuminate\Support\Facades\DB::table('esbtp_ue_matiere')->orderBy('id')->get() as $ligne) {
            $cle = $ligne->unite_enseignement_id . '_' . $ligne->matiere_id;
            if (isset($vus[$cle])) {
                $aSupprimer[] = $ligne->id;
                continue;
            }
            $vus[$cle] = true;
        }

        foreach (array_chunk($aSupprimer, 500) as $lot) {
            \Illuminate\Support\Facades\DB::table('esbtp_ue_matiere')->whereIn('id', $lot)->delete();
        }

        \Illuminate\Support\Facades\DB::table('esbtp_ue_matiere')->update(['parcours_id' => 0]);
    }
};
