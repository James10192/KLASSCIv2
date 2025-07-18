<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('esbtp_planifications_academiques', function (Blueprint $table) {
            $table->id();
            
            // Clés étrangères principales
            $table->foreignId('annee_universitaire_id')->constrained('esbtp_annee_universitaires')->onDelete('cascade');
            $table->foreignId('filiere_id')->constrained('esbtp_filieres')->onDelete('cascade');
            $table->foreignId('niveau_etude_id')->constrained('esbtp_niveau_etudes')->onDelete('cascade');
            $table->foreignId('matiere_id')->constrained('esbtp_matieres')->onDelete('cascade');
            
            // Informations académiques
            $table->integer('semestre')->comment('1 ou 2 pour semestre, 1-4 pour trimestre');
            $table->integer('volume_horaire_total')->comment('Volume horaire total en heures');
            $table->integer('volume_horaire_cm')->default(0)->comment('Heures de Cours Magistraux');
            $table->integer('volume_horaire_td')->default(0)->comment('Heures de Travaux Dirigés');
            $table->integer('volume_horaire_tp')->default(0)->comment('Heures de Travaux Pratiques');
            $table->decimal('coefficient', 5, 2)->default(1)->comment('Coefficient de la matière');
            $table->integer('credits_ects')->default(0)->comment('Crédits ECTS');
            
            // Périodes d'enseignement
            $table->date('periode_debut')->nullable()->comment('Date de début d\'enseignement');
            $table->date('periode_fin')->nullable()->comment('Date de fin d\'enseignement');
            
            // Enseignants
            $table->foreignId('enseignant_principal_id')->nullable()->constrained('users')->onDelete('set null');
            $table->json('enseignants_secondaires')->nullable()->comment('IDs des enseignants secondaires');
            
            // Contraintes et informations pédagogiques
            $table->json('contraintes_pedagogiques')->nullable()->comment('Contraintes spécifiques (matériel, salle, etc.)');
            $table->text('objectifs_pedagogiques')->nullable()->comment('Objectifs pédagogiques du cours');
            $table->text('prerequis')->nullable()->comment('Prérequis nécessaires');
            $table->json('modalites_evaluation')->nullable()->comment('Modalités d\'évaluation (examen, contrôle, etc.)');
            $table->json('ressources_necessaires')->nullable()->comment('Ressources nécessaires (salles, matériel)');
            
            // Statut et suivi
            $table->enum('statut', [
                'brouillon', 
                'planifie', 
                'valide', 
                'en_cours', 
                'termine', 
                'archive'
            ])->default('brouillon');
            
            $table->text('observations')->nullable()->comment('Observations et remarques');
            $table->boolean('is_active')->default(true);
            
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index pour les performances (noms raccourcis pour MySQL)
            $table->index(['annee_universitaire_id', 'filiere_id', 'niveau_etude_id'], 'idx_planif_annee_filiere_niveau');
            $table->index(['semestre', 'statut'], 'idx_planif_semestre_statut');
            $table->index(['enseignant_principal_id'], 'idx_planif_enseignant');
            $table->index(['periode_debut', 'periode_fin'], 'idx_planif_periodes');
            
            // Contrainte d'unicité : une matière ne peut être planifiée qu'une fois 
            // par semestre pour une combinaison filière/niveau/année
            $table->unique([
                'annee_universitaire_id', 
                'filiere_id', 
                'niveau_etude_id', 
                'matiere_id', 
                'semestre'
            ], 'uniq_planif_academique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('esbtp_planifications_academiques');
    }
};