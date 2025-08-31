<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateEsbtpFactureDetailsStructure extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('esbtp_facture_details', function (Blueprint $table) {
            // Renommer designation en description si elle existe ET si description n'existe pas déjà
            if (Schema::hasColumn('esbtp_facture_details', 'designation') && 
                !Schema::hasColumn('esbtp_facture_details', 'description')) {
                $table->renameColumn('designation', 'description');
            }

            // Renommer total_ligne en montant si elle existe ET si montant n'existe pas déjà
            if (Schema::hasColumn('esbtp_facture_details', 'total_ligne') && 
                !Schema::hasColumn('esbtp_facture_details', 'montant')) {
                $table->renameColumn('total_ligne', 'montant');
            }

            // Ajouter la colonne prix_unitaire si elle n'existe pas
            if (!Schema::hasColumn('esbtp_facture_details', 'prix_unitaire')) {
                $table->decimal('prix_unitaire', 10, 2)->after('description');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('esbtp_facture_details', function (Blueprint $table) {
            // Restaurer les noms originaux des colonnes
            if (Schema::hasColumn('esbtp_facture_details', 'description')) {
                $table->renameColumn('description', 'designation');
            }

            if (Schema::hasColumn('esbtp_facture_details', 'montant')) {
                $table->renameColumn('montant', 'total_ligne');
            }

            // Supprimer la colonne prix_unitaire si elle existe
            if (Schema::hasColumn('esbtp_facture_details', 'prix_unitaire')) {
                $table->dropColumn('prix_unitaire');
            }
        });
    }
}
