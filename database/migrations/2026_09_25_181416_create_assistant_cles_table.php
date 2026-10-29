<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Clés d'API des fournisseurs d'IA posées par l'école, chiffrées (cast
        // `encrypted`). Hors de la table settings, pour qu'aucun export, sauvegarde,
        // journal ou lecture générique des réglages ne puisse les emporter.
        Schema::create('assistant_cles', function (Blueprint $table) {
            $table->id();
            $table->string('fournisseur', 40)->unique();
            $table->text('cle');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('assistant_cles');
    }
};
