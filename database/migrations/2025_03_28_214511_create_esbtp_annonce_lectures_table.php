<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEsbtpAnnonceLecturesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('esbtp_annonce_lectures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('annonce_id');
            $table->unsignedBigInteger('etudiant_id');
            $table->datetime('read_at');
            $table->timestamps();

            // Clés étrangères
            $table->foreign('annonce_id')->references('id')->on('esbtp_annonces')->onDelete('cascade');
            $table->foreign('etudiant_id')->references('id')->on('esbtp_etudiants')->onDelete('cascade');

            // Index unique pour éviter les doublons
            $table->unique(['annonce_id', 'etudiant_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_annonce_lectures');
    }
}
