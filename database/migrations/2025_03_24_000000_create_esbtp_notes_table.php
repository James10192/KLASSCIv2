<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEsbtpNotesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        if (!Schema::hasTable('esbtp_notes')) {
            Schema::create('esbtp_notes', function (Blueprint $table) {
                $table->id();
                // Vérifier si la table esbtp_evaluations existe avant de créer la contrainte
                if (Schema::hasTable('esbtp_evaluations')) {
                    $table->foreignId('evaluation_id')->nullable()->constrained('esbtp_evaluations')->onDelete('cascade');
                } else {
                    $table->unsignedBigInteger('evaluation_id')->nullable();
                }
                $table->foreignId('matiere_id')->constrained('esbtp_matieres')->onDelete('cascade');
                $table->foreignId('etudiant_id')->constrained('esbtp_etudiants')->onDelete('cascade');
                $table->string('semestre')->nullable();
                $table->string('annee_universitaire')->nullable();
                $table->decimal('note', 8, 2);
                $table->string('type_evaluation')->nullable();
                $table->decimal('moyenne_matiere', 5, 2)->nullable();
                $table->integer('rang_matiere')->nullable();
                $table->text('appreciation')->nullable();
                $table->boolean('is_absent')->default(false);
                $table->foreignId('classe_id')->constrained('esbtp_classes');
                $table->foreignId('created_by')->nullable()->constrained('users');
                $table->foreignId('updated_by')->nullable()->constrained('users');
                $table->timestamps();
                $table->softDeletes();
            });

            // Ajouter la contrainte de clé étrangère plus tard si la table esbtp_evaluations est créée après
            if (!Schema::hasTable('esbtp_evaluations')) {
                Schema::table('esbtp_notes', function (Blueprint $table) {
                    $table->index('evaluation_id');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_notes');
    }
}
