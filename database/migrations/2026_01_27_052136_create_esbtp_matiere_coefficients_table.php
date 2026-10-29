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
        Schema::create('esbtp_matiere_coefficients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('matiere_id');
            $table->unsignedBigInteger('filiere_id');
            $table->unsignedBigInteger('niveau_etude_id');
            $table->unsignedBigInteger('annee_universitaire_id');
            $table->decimal('coefficient', 5, 2)->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['matiere_id', 'filiere_id', 'niveau_etude_id', 'annee_universitaire_id'], 'matiere_coeff_unique');

            $table->foreign('matiere_id')->references('id')->on('esbtp_matieres')->onDelete('cascade');
            $table->foreign('filiere_id')->references('id')->on('esbtp_filieres')->onDelete('cascade');
            $table->foreign('niveau_etude_id')->references('id')->on('esbtp_niveau_etudes')->onDelete('cascade');
            $table->foreign('annee_universitaire_id')->references('id')->on('esbtp_annee_universitaires')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_matiere_coefficients');
    }
};
