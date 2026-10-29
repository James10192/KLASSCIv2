<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddLibelleToEsbtpTables extends Migration
{
    public function up()
    {
        // Première étape : Ajouter les colonnes
        Schema::table('esbtp_annee_universitaires', function (Blueprint $table) {
            if (!Schema::hasColumn('esbtp_annee_universitaires', 'libelle')) {
                $table->string('libelle')->after('name')->nullable();
            }
        });

        Schema::table('esbtp_niveau_etudes', function (Blueprint $table) {
            if (!Schema::hasColumn('esbtp_niveau_etudes', 'libelle')) {
                $table->string('libelle')->after('name')->nullable();
            }
        });

        Schema::table('esbtp_classes', function (Blueprint $table) {
            if (!Schema::hasColumn('esbtp_classes', 'libelle')) {
                $table->string('libelle')->after('name')->nullable();
            }
        });

        Schema::table('esbtp_filieres', function (Blueprint $table) {
            if (!Schema::hasColumn('esbtp_filieres', 'libelle')) {
                $table->string('libelle')->after('name')->nullable();
            }
        });

        // Deuxième étape : Copier les données
        DB::statement('UPDATE esbtp_annee_universitaires SET libelle = name WHERE libelle IS NULL');
        DB::statement('UPDATE esbtp_niveau_etudes SET libelle = name WHERE libelle IS NULL');
        DB::statement('UPDATE esbtp_classes SET libelle = name WHERE libelle IS NULL');
        DB::statement('UPDATE esbtp_filieres SET libelle = name WHERE libelle IS NULL');
    }

    public function down()
    {
        Schema::table('esbtp_annee_universitaires', function (Blueprint $table) {
            if (Schema::hasColumn('esbtp_annee_universitaires', 'libelle')) {
                $table->dropColumn('libelle');
            }
        });

        Schema::table('esbtp_niveau_etudes', function (Blueprint $table) {
            if (Schema::hasColumn('esbtp_niveau_etudes', 'libelle')) {
                $table->dropColumn('libelle');
            }
        });

        Schema::table('esbtp_classes', function (Blueprint $table) {
            if (Schema::hasColumn('esbtp_classes', 'libelle')) {
                $table->dropColumn('libelle');
            }
        });

        Schema::table('esbtp_filieres', function (Blueprint $table) {
            if (Schema::hasColumn('esbtp_filieres', 'libelle')) {
                $table->dropColumn('libelle');
            }
        });
    }
}
