<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les prenoms du tuteur, a part de son nom.
 *
 * Le portail demandait « Nom et prenoms » en un seul champ ; a l'inscription,
 * l'ecole cree le parent avec un nom ET des prenoms, et la scolarite devait
 * redecouper a la main. Les candidatures deja deposees gardent tout dans
 * `tuteur_nom` : rien n'est redecoupe automatiquement, un nom ivoirien ne se
 * coupe pas sur le premier espace.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('esbtp_candidatures', 'tuteur_prenoms')) {
            return;
        }

        Schema::table('esbtp_candidatures', function (Blueprint $table) {
            $table->string('tuteur_prenoms', 150)->nullable()->after('tuteur_nom');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('esbtp_candidatures', 'tuteur_prenoms')) {
            return;
        }

        Schema::table('esbtp_candidatures', function (Blueprint $table) {
            $table->dropColumn('tuteur_prenoms');
        });
    }
};
