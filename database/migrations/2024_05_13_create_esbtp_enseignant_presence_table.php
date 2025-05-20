<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('esbtp_enseignant_presence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enseignant_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('matiere_id')->constrained('esbtp_matieres')->onDelete('cascade');
            $table->date('date');
            $table->time('heure_arrivee')->nullable();
            $table->time('heure_depart')->nullable();
            $table->string('statut')->default('present'); // present, absent, retard
            $table->text('remarques')->nullable();
            $table->string('adresse_ip')->nullable();
            $table->string('info_appareil')->nullable();
            $table->timestamps();

            // Add indexes for better performance
            $table->index(['date', 'enseignant_id']);
            $table->index(['matiere_id', 'date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('esbtp_enseignant_presence');
    }
};
