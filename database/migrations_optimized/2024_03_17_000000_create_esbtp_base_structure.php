<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Cette migration regroupe toutes les tables de base ESBTP :
     * - Structures académiques (niveaux, filières, années)  
     * - Gestion des classes et matières
     * - Tables de référence
     */
    public function up(): void
    {
        // Années universitaires
        Schema::create('esbtp_annee_universitaires', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('libelle');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->year('annee_debut');
            $table->boolean('est_actif')->default(false);
            $table->boolean('is_current')->default(false);
            $table->timestamps();
            
            $table->index(['est_actif', 'is_current']);
        });

        // Niveaux d'études
        Schema::create('esbtp_niveau_etudes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('libelle');
            $table->string('description')->nullable();
            $table->integer('ordre')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('is_active');
        });

        // Filières 
        Schema::create('esbtp_filieres', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('libelle');
            $table->text('description')->nullable();
            $table->string('code', 20)->unique();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('esbtp_filieres')->onDelete('set null');
            $table->index(['is_active', 'parent_id']);
        });

        // Table pivot filière-niveau
        Schema::create('esbtp_filiere_niveau', function (Blueprint $table) {
            $table->id();
            $table->foreignId('filiere_id')->constrained('esbtp_filieres')->onDelete('cascade');
            $table->foreignId('niveau_id')->constrained('esbtp_niveau_etudes')->onDelete('cascade');
            $table->integer('duree_etudes')->default(1);
            $table->text('conditions_acces')->nullable();
            $table->text('debouches')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['filiere_id', 'niveau_id']);
        });

        // Classes
        Schema::create('esbtp_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('libelle');
            $table->string('code', 20)->unique();
            $table->foreignId('filiere_id')->constrained('esbtp_filieres')->onDelete('cascade');
            $table->foreignId('niveau_etude_id')->constrained('esbtp_niveau_etudes')->onDelete('cascade');
            $table->foreignId('annee_universitaire_id')->constrained('esbtp_annee_universitaires')->onDelete('cascade');
            // Colonnes compatibles avec le modèle ESBTPClasse existant
            $table->integer('effectif_max')->default(40);
            $table->integer('places_disponibles')->default(40);
            $table->integer('places_reservees')->default(0);
            $table->integer('places_totales')->default(40); // Utilisé par le modèle
            $table->integer('places_occupees')->default(0); // Utilisé par le modèle
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes(); // Le modèle utilise SoftDeletes
            $table->timestamps();

            $table->index(['is_active', 'filiere_id', 'niveau_etude_id']);
            $table->index('annee_universitaire_id');
        });

        // Matières
        Schema::create('esbtp_matieres', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('libelle');
            $table->string('code', 20)->unique();
            $table->text('description')->nullable();
            $table->decimal('coefficient', 3, 1)->default(1.0);
            $table->enum('type', ['Théorique', 'Pratique', 'Mixte'])->default('Théorique');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['is_active', 'type']);
        });

        // Table pivot matière-niveau  
        Schema::create('esbtp_matiere_niveau', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matiere_id')->constrained('esbtp_matieres')->onDelete('cascade');
            $table->foreignId('niveau_id')->constrained('esbtp_niveau_etudes')->onDelete('cascade');
            $table->decimal('coefficient', 3, 1)->default(1.0);
            $table->integer('heures_cours')->default(0);
            $table->integer('heures_td')->default(0);
            $table->integer('heures_tp')->default(0);
            $table->boolean('is_obligatoire')->default(true);
            $table->timestamps();

            $table->unique(['matiere_id', 'niveau_id']);
        });

        // Table pivot matière-filière
        Schema::create('esbtp_matiere_filiere', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matiere_id')->constrained('esbtp_matieres')->onDelete('cascade');
            $table->foreignId('filiere_id')->constrained('esbtp_filieres')->onDelete('cascade');
            $table->decimal('coefficient', 3, 1)->default(1.0);
            $table->boolean('is_obligatoire')->default(true);
            $table->timestamps();

            $table->unique(['matiere_id', 'filiere_id']);
        });

        // Table pivot classe-matière
        Schema::create('esbtp_classe_matiere', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classe_id')->constrained('esbtp_classes')->onDelete('cascade');
            $table->foreignId('matiere_id')->constrained('esbtp_matieres')->onDelete('cascade');
            $table->decimal('coefficient', 3, 1)->default(1.0);
            $table->integer('heures_prevues')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['classe_id', 'matiere_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('esbtp_classe_matiere');
        Schema::dropIfExists('esbtp_matiere_filiere');
        Schema::dropIfExists('esbtp_matiere_niveau');
        Schema::dropIfExists('esbtp_matieres');
        Schema::dropIfExists('esbtp_classes');
        Schema::dropIfExists('esbtp_filiere_niveau');
        Schema::dropIfExists('esbtp_filieres');
        Schema::dropIfExists('esbtp_niveau_etudes');
        Schema::dropIfExists('esbtp_annee_universitaires');
    }
};