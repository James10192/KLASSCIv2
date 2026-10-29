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
        Schema::create('esbtp_enseignant_taux_seance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')
                ->constrained('esbtp_teachers')
                ->cascadeOnDelete();
            // Valeur d'enum App\Enums\TypeSeance (CM/TD/TP aujourd'hui, extensible).
            $table->string('type_seance', 20);
            $table->decimal('taux_horaire', 10, 2);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // Un seul taux par enseignant et par type de séance.
            $table->unique(['teacher_id', 'type_seance']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_enseignant_taux_seance');
    }
};
