<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateEsbtpEvaluationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('esbtp_evaluations')) {
            Schema::create('esbtp_evaluations', function (Blueprint $table) {
                $table->id();
                $table->string('titre')->nullable(); // Ajouté pour éviter les conflits avec d'autres migrations
                $table->text('description')->nullable(); // Ajouté pour éviter les conflits avec d'autres migrations
                $table->foreignId('matiere_id')->constrained('esbtp_matieres')->onDelete('cascade');
                $table->foreignId('classe_id')->constrained('esbtp_classes')->onDelete('cascade');
                $table->string('type'); // devoir, examen, rattrapage, etc.
                $table->date('date_evaluation');
                $table->decimal('coefficient', 3, 1)->default(1.0);
                $table->decimal('bareme', 5, 2)->default(20.00);
                $table->integer('duree_minutes')->nullable(); // Ajouté pour éviter les conflits avec d'autres migrations
                $table->string('periode')->nullable(); // semestre1, semestre2, etc. Ajouté nullable pour éviter les conflits
                $table->foreignId('annee_universitaire_id')->constrained('esbtp_annee_universitaires')->onDelete('cascade');
                $table->string('status')->default('draft'); // draft, scheduled, in_progress, completed, cancelled
                $table->boolean('is_published')->default(false);
                $table->boolean('notes_published')->default(false);
                $table->foreignId('created_by')->nullable()->constrained('users');
                $table->foreignId('updated_by')->nullable()->constrained('users');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Ajouter la clé étrangère à la table esbtp_notes si elle existe
        if (Schema::hasTable('esbtp_notes') && Schema::hasColumn('esbtp_notes', 'evaluation_id')) {
            try {
                // Vérifier d'abord si la contrainte existe déjà
                $foreignKeyExists = DB::select("
                    SELECT COUNT(*) as count FROM information_schema.TABLE_CONSTRAINTS
                    WHERE CONSTRAINT_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'esbtp_notes'
                    AND CONSTRAINT_NAME = 'esbtp_notes_evaluation_id_foreign'
                ");

                if (!$foreignKeyExists[0]->count) {
                    Schema::table('esbtp_notes', function (Blueprint $table) {
                        // Supprimer d'abord l'index si existant
                        if (Schema::hasTable('esbtp_notes') && $this->hasIndex('esbtp_notes', 'esbtp_notes_evaluation_id_index')) {
                            $table->dropIndex('esbtp_notes_evaluation_id_index');
                        }

                        // Ajouter la contrainte de clé étrangère
                        $table->foreign('evaluation_id')
                              ->references('id')
                              ->on('esbtp_evaluations')
                              ->onDelete('cascade');
                    });
                }
            } catch (\Exception $e) {
                // Logger l'erreur mais ne pas la laisser interrompre la migration
                error_log('Erreur lors de l\'ajout de la contrainte de clé étrangère: ' . $e->getMessage());
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Supprimer d'abord la contrainte de clé étrangère de esbtp_notes si elle existe
        if (Schema::hasTable('esbtp_notes') && Schema::hasColumn('esbtp_notes', 'evaluation_id')) {
            try {
                Schema::table('esbtp_notes', function (Blueprint $table) {
                    $table->dropForeign(['evaluation_id']);
                });
            } catch (\Exception $e) {
                // Logger l'erreur mais ne pas la laisser interrompre la migration
                error_log('Erreur lors de la suppression de la contrainte de clé étrangère: ' . $e->getMessage());
            }
        }

        Schema::dropIfExists('esbtp_evaluations');
    }

    /**
     * Vérifie si un index existe dans une table
     *
     * @param string $table
     * @param string $index
     * @return bool
     */
    private function hasIndex($table, $index)
    {
        // Depuis Laravel 11, le schema natif remplace Doctrine pour les index.
        return Schema::hasIndex($table, $index);
    }
}
