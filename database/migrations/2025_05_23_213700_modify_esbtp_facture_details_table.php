<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyEsbtpFactureDetailsTable extends Migration
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
        });
    }
}
