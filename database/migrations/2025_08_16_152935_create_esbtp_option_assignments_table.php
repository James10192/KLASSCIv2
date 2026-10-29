<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEsbtpOptionAssignmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('esbtp_option_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('option_id'); // ID de l'option (variant)
            $table->unsignedBigInteger('filiere_id')->nullable(); // ID filière (null = tous)
            $table->unsignedBigInteger('niveau_id')->nullable(); // ID niveau (null = tous)
            $table->enum('assignment_type', ['all', 'filiere', 'niveau', 'classe'])->default('all');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Index et clés étrangères
            $table->index(['option_id', 'filiere_id', 'niveau_id']);
            $table->foreign('option_id')->references('id')->on('esbtp_frais_options')->onDelete('cascade');
            $table->foreign('filiere_id')->references('id')->on('esbtp_filieres')->onDelete('cascade');
            $table->foreign('niveau_id')->references('id')->on('esbtp_niveau_etudes')->onDelete('cascade');
            
            // Contrainte unique pour éviter les doublons
            $table->unique(['option_id', 'filiere_id', 'niveau_id'], 'unique_option_assignment');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_option_assignments');
    }
}
