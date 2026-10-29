<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('esbtp_examens_planifies', function (Blueprint $table) {
            $table->id();

            // Scope académique
            $table->foreignId('annee_universitaire_id')
                ->constrained('esbtp_annee_universitaires')
                ->cascadeOnDelete();
            $table->foreignId('classe_id')
                ->constrained('esbtp_classes')
                ->cascadeOnDelete();
            $table->foreignId('matiere_id')
                ->constrained('esbtp_matieres')
                ->cascadeOnDelete();
            $table->foreignId('parcours_id')
                ->nullable()
                ->constrained('esbtp_lmd_parcours')
                ->nullOnDelete();
            $table->unsignedTinyInteger('semestre')->nullable();

            // Session
            $table->foreignId('session_id')
                ->nullable()
                ->comment('FK vers esbtp_lmd_sessions (PR10) - nullable pour rétrocompat');

            // Type d'épreuve canonique UEMOA
            $table->string('type_examen', 32)->default('EXAMEN')
                ->comment('EXAMEN|PARTIEL|RATTRAPAGE|SOUTENANCE');

            // Identité de l'épreuve
            $table->string('titre');
            $table->text('description')->nullable();
            $table->string('numero_convocation', 64)->nullable()->unique()
                ->comment('CONV-{TENANT}-{ANNEE}-{SEQ}');

            // Planning
            $table->dateTime('date_debut');
            $table->dateTime('date_fin');
            $table->unsignedSmallInteger('duree_minutes')->nullable();
            $table->string('salle')->nullable();

            // Pondération
            $table->decimal('coefficient', 5, 2)->default(1);
            $table->decimal('bareme', 5, 2)->default(20);

            // Anonymat & confidentialité
            $table->boolean('is_anonymous')->default(false)
                ->comment('Copies anonymisées via numero_anonymat');

            // Workflow state machine
            $table->string('status', 32)->default('planned')
                ->comment('draft|planned|in_progress|completed|notes_locked|cancelled');
            $table->boolean('notes_locked')->default(false)
                ->comment('Anti-tampering : empêche modification notes après lock');
            $table->dateTime('notes_locked_at')->nullable();
            $table->foreignId('notes_locked_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Audit minimal
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Indexes performance
            $table->index(['annee_universitaire_id', 'classe_id', 'semestre'], 'idx_examens_scope');
            $table->index(['date_debut', 'date_fin'], 'idx_examens_planning');
            $table->index(['status'], 'idx_examens_status');
            $table->index(['type_examen', 'parcours_id'], 'idx_examens_type_parcours');
        });
    }

    public function down()
    {
        Schema::dropIfExists('esbtp_examens_planifies');
    }
};
