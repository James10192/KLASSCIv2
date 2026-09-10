<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ordre d'affichage d'une unite d'enseignement.
 *
 * Sert uniquement de repli : quand une classe LMD n'a pas de parcours, les UE
 * d'un semestre sont listees par code faute de mieux. Le pivot
 * `esbtp_lmd_parcours_ue` porte deja un `ordre` pour le cas nominal.
 *
 * Additif, nullable, sans defaut : tant qu'aucun ordre n'est pose, le tri par
 * code d'aujourd'hui est conserve a l'identique.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('esbtp_unites_enseignement', function (Blueprint $table) {
            if (! Schema::hasColumn('esbtp_unites_enseignement', 'ordre')) {
                $table->unsignedSmallInteger('ordre')->nullable()->after('semestre');
            }
        });
    }

    public function down(): void
    {
        Schema::table('esbtp_unites_enseignement', function (Blueprint $table) {
            if (Schema::hasColumn('esbtp_unites_enseignement', 'ordre')) {
                $table->dropColumn('ordre');
            }
        });
    }
};
