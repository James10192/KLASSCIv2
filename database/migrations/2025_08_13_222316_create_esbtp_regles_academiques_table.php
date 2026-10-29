<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEsbtpReglesAcademiquesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('esbtp_regles_academiques', function (Blueprint $table) {
            $table->id();
            $table->string('niveau'); // BTS1, BTS2, etc.
            $table->string('filiere');
            $table->decimal('moyenne_passage', 4, 2); // Note minimum pour passer
            $table->decimal('moyenne_rattrapage', 4, 2); // Note minimum pour rattrapage
            $table->integer('max_matieres_rattrapage')->default(3); // Nombre max de matières en rattrapage
            $table->boolean('autoriser_redoublement')->default(true);
            $table->integer('max_redoublements')->default(1);
            $table->text('conditions_speciales')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
            
            $table->unique(['niveau', 'filiere'], 'unique_regle_niveau_filiere');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_regles_academiques');
    }
}
