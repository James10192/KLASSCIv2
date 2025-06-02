<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlignEsbtpFactureDetailsStructure extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('esbtp_facture_details', function (Blueprint $table) {
            // Ajouter la colonne prix_unitaire
            $table->decimal('prix_unitaire', 10, 2)->after('description');

            // Renommer designation en description si la colonne existe
            if (Schema::hasColumn('esbtp_facture_details', 'designation')) {
                $table->renameColumn('designation', 'description');
            }

            // Renommer total_ligne en montant si la colonne existe
            if (Schema::hasColumn('esbtp_facture_details', 'total_ligne')) {
                $table->renameColumn('total_ligne', 'montant');
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
            // Supprimer la colonne prix_unitaire
            $table->dropColumn('prix_unitaire');

            // Restaurer les noms originaux des colonnes
            if (Schema::hasColumn('esbtp_facture_details', 'description')) {
                $table->renameColumn('description', 'designation');
            }

            if (Schema::hasColumn('esbtp_facture_details', 'montant')) {
                $table->renameColumn('montant', 'total_ligne');
            }
        });
    }
}
