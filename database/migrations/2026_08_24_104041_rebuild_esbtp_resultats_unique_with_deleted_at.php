<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La cle unique d'esbtp_resultats ignorait `deleted_at` : une ligne supprimee
 * occupait toujours la cle. Quand une note revenait sur la matiere, la
 * generation tentait un INSERT sur le meme quintuple et prenait un
 * `Duplicate entry` -- 500 definitif sur cet etudiant, sans issue depuis
 * l'interface.
 *
 * MySQL considere les NULL comme distincts dans un index unique : les lignes
 * vivantes (`deleted_at IS NULL`) restent uniques entre elles, les lignes
 * supprimees ne collisionnent plus avec rien.
 *
 * Ordre impose par MySQL (erreur 1553) : des cles etrangeres s'adossent a
 * l'index unique. On CREE d'abord le nouvel index -- qui commence par les
 * memes colonnes et peut donc porter les FK -- puis on supprime l'ancien.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('esbtp_resultats', function (Blueprint $table) {
            $table->unique(
                ['etudiant_id', 'classe_id', 'matiere_id', 'periode', 'annee_universitaire_id', 'deleted_at'],
                'esbtp_resultats_unique_v2'
            );
        });

        Schema::table('esbtp_resultats', function (Blueprint $table) {
            $table->dropUnique('esbtp_resultats_unique');
        });
    }

    public function down(): void
    {
        Schema::table('esbtp_resultats', function (Blueprint $table) {
            $table->unique(
                ['etudiant_id', 'classe_id', 'matiere_id', 'periode', 'annee_universitaire_id'],
                'esbtp_resultats_unique'
            );
        });

        Schema::table('esbtp_resultats', function (Blueprint $table) {
            $table->dropUnique('esbtp_resultats_unique_v2');
        });
    }
};
