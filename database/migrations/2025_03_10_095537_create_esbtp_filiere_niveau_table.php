<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEsbtpFiliereNiveauTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('esbtp_filiere_niveau', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('filiere_id');
            $table->unsignedBigInteger('niveau_etude_id');
            $table->timestamps();

            $table->foreign('filiere_id')->references('id')->on('esbtp_filieres')->onDelete('cascade');
            $table->foreign('niveau_etude_id')->references('id')->on('esbtp_niveau_etudes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_filiere_niveau');
    }
}
