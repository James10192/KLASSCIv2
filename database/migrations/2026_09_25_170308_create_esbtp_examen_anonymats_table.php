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
        Schema::create('esbtp_examen_anonymats', function (Blueprint $table) {
            $table->id();
            // Un numéro par copie : le correcteur saisit la note face au
            // numéro, le nom n'apparaît qu'à la levée de l'anonymat.
            $table->foreignId('examen_planifie_id')->constrained('esbtp_examens_planifies')->cascadeOnDelete();
            $table->foreignId('etudiant_id')->constrained('esbtp_etudiants')->cascadeOnDelete();
            $table->string('numero', 20);
            $table->timestamps();

            $table->unique(['examen_planifie_id', 'etudiant_id']);
            $table->unique(['examen_planifie_id', 'numero']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_examen_anonymats');
    }
};
