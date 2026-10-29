<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permet a une maquette de donner son propre poids en credits a une unite
 * qu elle partage.
 *
 * L unite est partagee, son code est unique dans l ecole : `credit` ne peut donc
 * dire qu une seule chose pour tout le monde. Or deux parcours qui partagent la
 * meme unite peuvent legitimement lui accorder un poids different — la directive
 * UEMOA impose 30 credits par semestre, elle ne prescrit rien par unite.
 *
 * PORTEE DE CE CREDIT — tranche explicitement : par (parcours, unite, SEMESTRE).
 * C est la ligne elle-meme, puisque l unicite de cette table est deja
 * (parcours_id, unite_enseignement_id, semestre). Une unite placee dans deux
 * semestres d une meme maquette donne donc DEUX lignes, et peut porter deux
 * credits. Ce n est pas un effet de bord : sept unites de Batiment sont, sur
 * esbtp-abidjan, rattachees a deux semestres — les rassembler sous un credit
 * unique par (parcours, unite) aurait force un arbitrage que personne n a
 * demande, et fausse la somme des 30 credits d au moins un des deux semestres.
 *
 * NUL N EST PAS ZERO. `null` veut dire « cette maquette n a pas de credit
 * propre, elle prend celui de l unite » ; `0` voudrait dire « cette maquette lui
 * accorde zero credit », ce qui est une decision. Tout code qui lira cette
 * colonne doit distinguer les deux — un `?:` ou un `??` mal choisi ici efface un
 * arbitrage de l ecole sans rien afficher.
 *
 * Cette migration N ACTIVE RIEN : la colonne nait nulle partout, ce qui est
 * exactement le comportement actuel (le credit de l unite pour tout le monde).
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('esbtp_lmd_parcours_ue', function (Blueprint $table) {
            if (! Schema::hasColumn('esbtp_lmd_parcours_ue', 'credit')) {
                // Nullable SANS defaut : une ligne creee par le service de
                // synchronisation, qui n ecrit que semestre/is_optional/ordre,
                // nait donc a null — « pas de credit propre ». C est ce qu il
                // faut : il n invente pas un credit qu on ne lui a pas donne.
                $table->unsignedSmallInteger('credit')->nullable()->after('is_optional');
            }
        });
    }

    public function down()
    {
        Schema::table('esbtp_lmd_parcours_ue', function (Blueprint $table) {
            if (Schema::hasColumn('esbtp_lmd_parcours_ue', 'credit')) {
                $table->dropColumn('credit');
            }
        });
    }
};
