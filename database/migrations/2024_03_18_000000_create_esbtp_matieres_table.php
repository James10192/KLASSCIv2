<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateESBTPMatieresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('esbtp_matieres', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->integer('coefficient')->default(1);
            $table->integer('heures_cm')->default(0); // Cours magistraux
            $table->integer('heures_td')->default(0); // Travaux dirigés
            $table->integer('heures_tp')->default(0); // Travaux pratiques
            $table->integer('heures_stage')->default(0); // Stages
            $table->integer('heures_perso')->default(0); // Travail personnel
            $table->foreignId('niveau_etude_id')->nullable()->constrained('esbtp_niveau_etudes');
            $table->foreignId('filiere_id')->nullable()->constrained('esbtp_filieres');
            $table->enum('type_formation', ['generale', 'technologique_professionnelle'])->default('generale');
            $table->string('couleur')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('name');
            $table->index('code');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_matieres');
    }
}
