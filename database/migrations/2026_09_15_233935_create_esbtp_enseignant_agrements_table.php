<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('esbtp_enseignant_agrements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id');
            $table->string('emetteur', 191)->nullable();
            $table->string('numero', 100)->nullable();
            $table->date('date_effet');
            $table->date('date_expiration')->nullable();
            $table->string('statut', 32)->default('actif');
            $table->text('motif_reexamen')->nullable();
            $table->timestamps();

            $table->foreign('teacher_id')->references('id')->on('esbtp_teachers')->cascadeOnDelete();
            $table->index(['teacher_id', 'statut']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('esbtp_enseignant_agrements');
    }
};
