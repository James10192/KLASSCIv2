<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le bloc du bulletin — enseignement general ou professionnel — rejoint la place
 * et le semestre sur le couple (filiere, niveau).
 *
 * Il vivait jusqu'ici classe par classe, dans `esbtp_config_matieres`. ESBTP
 * Abidjan devait donc refaire la meme classification pour 1A BTS A, puis B, puis
 * D : trois saisies identiques, trois occasions de diverger, et trois bulletins
 * qui pouvaient ne pas avoir la meme structure pour le meme programme.
 *
 * La colonne est nullable a dessein : nulle veut dire « cette ecole n'a rien dit
 * a ce niveau-la », et la resolution continue alors sa descente. Aucune valeur
 * n'est retro-remplie — une classification deduite du type global de la matiere
 * serait une invention, pas une reprise.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('esbtp_matiere_filiere_niveau', function (Blueprint $table) {
            $table->string('type_formation', 32)->nullable()->after('classification');
            $table->index(['filiere_id', 'niveau_etude_id', 'type_formation'], 'idx_mfn_combo_bloc');
        });
    }

    public function down(): void
    {
        Schema::table('esbtp_matiere_filiere_niveau', function (Blueprint $table) {
            $table->dropIndex('idx_mfn_combo_bloc');
            $table->dropColumn('type_formation');
        });
    }
};
