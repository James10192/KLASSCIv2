<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddConfigMatieresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('esbtp_config_matieres')) {
            Schema::create('esbtp_config_matieres', function (Blueprint $table) {
                $table->id();
                $table->foreignId('matiere_id')->constrained('esbtp_matieres');
                $table->foreignId('classe_id')->constrained('esbtp_classes');
                $table->foreignId('annee_universitaire_id')->constrained('esbtp_annee_universitaires');
                $table->string('periode'); // semestre1, semestre2, etc.
                $table->decimal('coefficient', 3, 1)->default(1.0);
                $table->decimal('nb_heures_cours', 5, 2)->default(0);
                $table->decimal('nb_heures_td', 5, 2)->default(0);
                $table->decimal('nb_heures_tp', 5, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                // Utiliser un nom d'index plus court
                $table->unique(['classe_id', 'annee_universitaire_id', 'periode', 'matiere_id'], 'config_matieres_unique_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_config_matieres');
    }
}
