<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Credit de l'unite PROPRE A UN PARCOURS.
 *
 * Une unite partagee peut peser un nombre de credits different d'une maquette
 * a l'autre : c'est le cas constate sur les maquettes de Genie Civil, ou la
 * meme unite ne vaut pas la meme chose en Batiment et en Travaux Publics.
 * Le referentiel UEMOA ne prescrit rien a l'echelle de l'unite — il n'impose
 * que les trente credits du semestre — donc l'ecart est legitime et ne doit
 * pas etre normalise contre la maquette que l'ecole a signee.
 *
 * Nullable a dessein : NULL veut dire « rien de particulier pour ce parcours,
 * on prend le credit de l'unite ». Le comportement d'aujourd'hui est donc
 * exactement conserve tant que personne ne renseigne cette colonne.
 *
 * Emprunte au lot « ecritures », idempotente pour la meme raison que sa jumelle.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('esbtp_lmd_parcours_ue', 'credit')) {
            return;
        }

        Schema::table('esbtp_lmd_parcours_ue', function (Blueprint $table) {
            $table->unsignedSmallInteger('credit')
                ->nullable()
                ->after('semestre')
                ->comment('NULL = on retient le credit porte par l\'UE elle-meme');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('esbtp_lmd_parcours_ue', 'credit')) {
            return;
        }

        Schema::table('esbtp_lmd_parcours_ue', function (Blueprint $table) {
            $table->dropColumn('credit');
        });
    }
};
