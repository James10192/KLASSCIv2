<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Cette migration créé les tables additionnelles nécessaires au fonctionnement de l'app :
     * - Paiements et frais
     * - Évaluations et notes
     * - Annonces
     * - Autres tables critiques
     */
    public function up(): void
    {
        // Table des paiements (simplifiée)
        Schema::create('esbtp_paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etudiant_id')->constrained('esbtp_etudiants')->onDelete('cascade');
            $table->foreignId('inscription_id')->nullable()->constrained('esbtp_inscriptions')->onDelete('set null');
            $table->decimal('montant', 10, 2);
            $table->date('date_paiement');
            $table->string('mode_paiement');
            $table->string('reference')->unique();
            $table->enum('statut', ['En attente', 'Validé', 'Rejeté'])->default('En attente');
            $table->text('commentaires')->nullable();
            $table->timestamps();
            
            $table->index(['etudiant_id', 'date_paiement']);
        });

        // Table des évaluations (simplifiée mais compatible)
        Schema::create('esbtp_evaluations', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('type'); // Devoir, Examen, etc.
            $table->foreignId('matiere_id')->constrained('esbtp_matieres')->onDelete('cascade');
            $table->foreignId('classe_id')->constrained('esbtp_classes')->onDelete('cascade');
            $table->date('date_evaluation');
            $table->decimal('note_sur', 5, 2)->default(20);
            $table->text('description')->nullable();
            $table->foreignId('enseignant_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('periode')->nullable(); // Compatible avec l'existant
            $table->timestamps();
            
            $table->index(['classe_id', 'matiere_id', 'date_evaluation']);
        });

        // Table des notes (compatible avec modèle existant)
        Schema::create('esbtp_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained('esbtp_evaluations')->onDelete('cascade');
            $table->foreignId('etudiant_id')->constrained('esbtp_etudiants')->onDelete('cascade');
            $table->decimal('note', 5, 2)->nullable();
            $table->boolean('is_absent')->default(false);
            $table->text('commentaire')->nullable();
            $table->foreignId('enseignant_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->unique(['evaluation_id', 'etudiant_id']);
            $table->index(['etudiant_id', 'evaluation_id']);
        });

        // Table des annonces (compatible avec système existant)
        Schema::create('esbtp_annonces', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->text('contenu');
            $table->enum('type', ['general', 'classe', 'etudiant'])->default('general');
            $table->enum('priorite', [0, 1, 2])->default(0); // 0=normale, 1=importante, 2=urgente
            $table->boolean('is_published')->default(false);
            $table->timestamp('date_publication')->nullable();
            $table->timestamp('date_expiration')->nullable();
            $table->string('piece_jointe')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['is_published', 'date_publication']);
            $table->index(['type', 'priorite']);
        });

        // Table pivot annonce-classe
        Schema::create('esbtp_annonce_classe', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annonce_id')->constrained('esbtp_annonces')->onDelete('cascade');
            $table->foreignId('classe_id')->constrained('esbtp_classes')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['annonce_id', 'classe_id']);
        });

        // Table pivot annonce-étudiant
        Schema::create('esbtp_annonce_etudiant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annonce_id')->constrained('esbtp_annonces')->onDelete('cascade');
            $table->foreignId('etudiant_id')->constrained('esbtp_etudiants')->onDelete('cascade');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            
            $table->unique(['annonce_id', 'etudiant_id']);
        });

        // Table des lectures d'annonces (compatible)
        Schema::create('esbtp_annonce_lectures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annonce_id')->constrained('esbtp_annonces')->onDelete('cascade');
            $table->foreignId('etudiant_id')->constrained('esbtp_etudiants')->onDelete('cascade');
            $table->timestamp('read_at');
            $table->timestamps();
            
            $table->unique(['annonce_id', 'etudiant_id']);
        });

        // Table des enseignants (simplifiée)
        Schema::create('esbtp_teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('matricule')->unique();
            $table->string('specialite')->nullable();
            $table->string('grade')->nullable();
            $table->enum('statut', ['Actif', 'Inactif', 'Suspendu'])->default('Actif');
            $table->date('date_embauche')->nullable();
            $table->text('cv_path')->nullable();
            $table->timestamps();
            
            $table->index(['statut', 'specialite']);
        });

        // Table des séances de cours (simplifiée)
        Schema::create('esbtp_seance_cours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classe_id')->constrained('esbtp_classes')->onDelete('cascade');
            $table->foreignId('matiere_id')->constrained('esbtp_matieres')->onDelete('cascade');
            $table->foreignId('teacher_id')->nullable()->constrained('esbtp_teachers')->onDelete('set null');
            $table->date('date_seance');
            $table->time('heure_debut');
            $table->time('heure_fin');
            $table->string('salle')->nullable();
            $table->enum('statut', ['Programmée', 'En cours', 'Terminée', 'Annulée'])->default('Programmée');
            $table->text('contenu')->nullable();
            $table->timestamps();
            
            $table->index(['classe_id', 'date_seance']);
            $table->index(['teacher_id', 'date_seance']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('esbtp_seance_cours');
        Schema::dropIfExists('esbtp_teachers');
        Schema::dropIfExists('esbtp_annonce_lectures');
        Schema::dropIfExists('esbtp_annonce_etudiant');
        Schema::dropIfExists('esbtp_annonce_classe');
        Schema::dropIfExists('esbtp_annonces');
        Schema::dropIfExists('esbtp_notes');
        Schema::dropIfExists('esbtp_evaluations');
        Schema::dropIfExists('esbtp_paiements');
    }
};