<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('esbtp_releves_bancaires', function (Blueprint $table) {
            $table->id();
            $table->string('identifiant_externe', 191)->nullable();
            $table->string('mode', 32);
            $table->decimal('montant', 15, 2);
            $table->date('date_valeur');
            $table->string('libelle')->nullable();
            $table->string('etat', 32)->default('importe');
            $table->timestamps();

            $table->unique('identifiant_externe');
        });
    }

    public function down()
    {
        Schema::dropIfExists('esbtp_releves_bancaires');
    }
};
