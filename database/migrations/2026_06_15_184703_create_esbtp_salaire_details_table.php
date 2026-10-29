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
        Schema::create('esbtp_salaire_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salaire_id')->constrained('esbtp_salaires')->cascadeOnDelete();
            // gain (heures×taux, prime) ou retenue (impot ITS, CNPS, avance, autre)
            $table->enum('categorie', ['gain', 'retenue']);
            // CM/TD/TP/prime pour les gains ; impot/cnps/avance/autre pour les retenues
            $table->string('type', 20);
            $table->string('libelle');
            $table->decimal('heures', 8, 2)->nullable();
            $table->decimal('taux', 10, 2)->nullable();
            $table->decimal('montant', 12, 2);
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->timestamps();

            $table->index(['salaire_id', 'categorie']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_salaire_details');
    }
};
