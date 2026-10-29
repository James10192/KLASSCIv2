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
        Schema::create('esbtp_prolongations_seance', function (Blueprint $table) {
            $table->id();
            // Une prolongation vaut pour UNE occurrence (séance + date), jamais
            // pour la séance récurrente : rallonger le cours de ce jeudi ne
            // doit pas rallonger tous les jeudis du semestre.
            $table->foreignId('seance_cours_id')->constrained('esbtp_seance_cours')->cascadeOnDelete();
            $table->date('date');
            $table->time('heure_fin_initiale');
            $table->time('heure_fin_demandee');
            $table->unsignedSmallInteger('minutes');
            $table->string('motif', 500);
            $table->string('statut', 20)->default('en_attente'); // en_attente, accordee, refusee, annulee
            $table->foreignId('demandee_par')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('decidee_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decidee_le')->nullable();
            $table->string('motif_decision', 500)->nullable();
            // Les conflits trouvés au moment de la décision, gardés pour l'audit :
            // un refus doit pouvoir s'expliquer après coup.
            $table->json('conflits')->nullable();
            $table->timestamps();

            $table->index(['seance_cours_id', 'date']);
            $table->index('statut');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_prolongations_seance');
    }
};
