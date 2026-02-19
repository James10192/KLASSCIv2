<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esbtp_matiere_filiere_niveau', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('matiere_id');
            $table->unsignedBigInteger('filiere_id');
            $table->unsignedBigInteger('niveau_etude_id');
            $table->timestamps();

            $table->unique(['matiere_id', 'filiere_id', 'niveau_etude_id']);

            $table->foreign('matiere_id')->references('id')->on('esbtp_matieres')->onDelete('cascade');
            $table->foreign('filiere_id')->references('id')->on('esbtp_filieres')->onDelete('cascade');
            $table->foreign('niveau_etude_id')->references('id')->on('esbtp_niveau_etudes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esbtp_matiere_filiere_niveau');
    }
};
