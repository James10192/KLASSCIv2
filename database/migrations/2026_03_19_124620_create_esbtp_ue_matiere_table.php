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
        Schema::create('esbtp_ue_matiere', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unite_enseignement_id')->constrained('esbtp_unites_enseignement')->cascadeOnDelete();
            $table->foreignId('matiere_id')->constrained('esbtp_matieres')->cascadeOnDelete();
            $table->decimal('coefficient_ecue', 5, 2)->nullable();
            $table->unsignedSmallInteger('credit_ecue')->nullable();
            $table->unsignedSmallInteger('ordre_bulletin')->default(0);
            $table->timestamps();

            $table->unique(['unite_enseignement_id', 'matiere_id'], 'ue_matiere_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('esbtp_ue_matiere');
    }
};
