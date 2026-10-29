<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le transfere qui redouble le niveau qu'il avait atteint ailleurs.
 *
 * L'ecole le demandait par telephone. A l'inscription, KLASSCI deduit le
 * statut redoublant du niveau de l'an dernier, mais un transfere n'a pas
 * d'annee precedente ici : sans sa reponse, la question « Redoublant ? » de
 * « Accepter et inscrire » partait toujours sur « Non ».
 *
 * Nullable, sans defaut : les candidatures deja deposees n'ont jamais eu la
 * question. Un `false` affirmerait une reponse que personne n'a donnee.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('esbtp_candidatures', function (Blueprint $table) {
            $table->boolean('redouble_niveau_origine')->nullable()->after('niveau_atteint_origine');
        });
    }

    public function down(): void
    {
        Schema::table('esbtp_candidatures', function (Blueprint $table) {
            $table->dropColumn('redouble_niveau_origine');
        });
    }
};
