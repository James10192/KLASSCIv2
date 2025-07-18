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
        // Créer une table dédiée pour les profils enseignants
        Schema::create('esbtp_enseignant_profiles', function (Blueprint $table) {
            $table->id();
            
            // Liaison avec le User
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Informations professionnelles avancées
            $table->string('matricule_enseignant')->unique()->nullable();
            $table->string('titre_academique')->nullable(); // Dr., Prof., M., Mme, etc.
            $table->string('grade_academique')->nullable(); // Assistant, Maître-Assistant, Maître de Conférences, etc.
            $table->string('diplome_principal')->nullable(); // Diplôme le plus élevé
            $table->string('universite_diplome')->nullable(); // Université d'obtention
            $table->year('annee_diplome')->nullable();
            
            // Spécialisations et compétences
            $table->json('specialites')->nullable(); // Domaines de spécialisation
            $table->json('competences_techniques')->nullable(); // Compétences spécifiques
            $table->json('certifications')->nullable(); // Certifications professionnelles
            $table->json('langues')->nullable(); // Langues parlées avec niveaux
            
            // Expérience professionnelle
            $table->integer('annees_experience_enseignement')->default(0);
            $table->integer('annees_experience_professionnelle')->default(0);
            $table->json('experiences_anterieures')->nullable(); // Postes précédents
            $table->json('projets_recherche')->nullable(); // Projets de recherche
            $table->json('publications')->nullable(); // Publications académiques
            
            // Disponibilités et préférences
            $table->json('disponibilites_hebdomadaires')->nullable(); // Créneaux disponibles
            $table->json('preferences_horaires')->nullable(); // Préférences d'horaires
            $table->json('contraintes_horaires')->nullable(); // Contraintes spécifiques
            $table->integer('charge_horaire_max_semaine')->default(40); // Charge maximale par semaine
            $table->integer('charge_horaire_actuelle')->default(0); // Charge actuelle
            
            // Évaluation et performance
            $table->decimal('note_evaluation_moyenne', 3, 2)->nullable(); // Note moyenne des évaluations
            $table->integer('nombre_evaluations')->default(0);
            $table->json('evaluations_competences')->nullable(); // Évaluations par compétence
            $table->decimal('taux_assiduite', 5, 2)->default(100.00); // Taux de présence
            $table->integer('nombre_retards')->default(0);
            $table->integer('nombre_absences')->default(0);
            
            // Formation continue
            $table->json('formations_suivies')->nullable(); // Formations continues
            $table->json('formations_prevues')->nullable(); // Formations planifiées
            $table->date('derniere_formation')->nullable();
            
            // Informations contractuelles
            $table->enum('type_contrat', ['permanent', 'temporaire', 'vacataire', 'consultant'])->default('permanent');
            $table->enum('statut_emploi', ['temps_plein', 'temps_partiel', 'vacations'])->default('temps_plein');
            $table->decimal('taux_horaire', 10, 2)->nullable(); // Taux horaire si applicable
            $table->date('date_embauche')->nullable();
            $table->date('fin_contrat')->nullable(); // Si contrat temporaire
            
            // Préférences pédagogiques
            $table->json('methodes_enseignement_preferees')->nullable(); // Méthodes préférées
            $table->json('outils_pedagogiques_maitrise')->nullable(); // Outils maîtrisés
            $table->boolean('accepte_enseignement_distance')->default(false);
            $table->boolean('accepte_cours_weekend')->default(false);
            $table->boolean('accepte_cours_soir')->default(false);
            
            // Motivation et objectifs
            $table->text('motivation')->nullable(); // Motivation pour enseigner
            $table->text('objectifs_pedagogiques')->nullable(); // Objectifs pédagogiques
            $table->text('projets_innovants')->nullable(); // Projets pédagogiques innovants
            
            // Statut et validation
            $table->enum('statut', ['actif', 'inactif', 'suspendu', 'en_formation', 'conge'])->default('actif');
            $table->boolean('profil_valide')->default(false);
            $table->foreignId('valide_par')->nullable()->constrained('users')->onDelete('set null');
            $table->datetime('date_validation')->nullable();
            
            // Audit et suivi
            $table->text('observations_rh')->nullable(); // Observations RH
            $table->text('notes_direction')->nullable(); // Notes de la direction
            $table->json('historique_modifications')->nullable(); // Historique des changements
            
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index pour optimiser les performances
            $table->index(['user_id']);
            $table->index(['statut']);
            $table->index(['type_contrat']);
            $table->index(['grade_academique']);
            $table->index(['profil_valide']);
            $table->index(['charge_horaire_actuelle']);
        });
        
        // Table pour les disponibilités détaillées des enseignants
        Schema::create('esbtp_enseignant_disponibilites', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('enseignant_profile_id')->constrained('esbtp_enseignant_profiles')->onDelete('cascade');
            
            // Définition de la disponibilité
            $table->integer('jour_semaine'); // 0=Lundi, 1=Mardi, etc.
            $table->time('heure_debut');
            $table->time('heure_fin');
            $table->enum('type_disponibilite', ['disponible', 'prefere', 'eviter', 'indisponible'])->default('disponible');
            $table->string('motif')->nullable(); // Motif si indisponible
            
            // Période de validité
            $table->date('date_debut')->nullable(); // Si disponibilité temporaire
            $table->date('date_fin')->nullable();
            
            // Récurrence
            $table->boolean('est_recurrent')->default(true); // Si répété chaque semaine
            $table->json('semaines_exception')->nullable(); // Semaines d'exception
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Index pour optimisation
            $table->index(['enseignant_profile_id', 'jour_semaine'], 'idx_ens_dispo_jour');
            $table->index(['type_disponibilite'], 'idx_ens_dispo_type');
            $table->index(['heure_debut', 'heure_fin'], 'idx_ens_dispo_heures');
        });
        
        // Table pour l'historique des affectations
        Schema::create('esbtp_enseignant_affectations', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('enseignant_profile_id')->constrained('esbtp_enseignant_profiles')->onDelete('cascade');
            $table->foreignId('planification_id')->nullable()->constrained('esbtp_planifications_academiques')->onDelete('set null');
            $table->foreignId('matiere_id')->constrained('esbtp_matieres')->onDelete('cascade');
            $table->foreignId('classe_id')->nullable()->constrained('esbtp_classes')->onDelete('set null');
            
            // Détails de l'affectation
            $table->enum('type_affectation', ['principal', 'secondaire', 'remplacant', 'temporaire']);
            $table->integer('heures_affectees'); // Nombre d'heures affectées
            $table->enum('type_cours', ['cm', 'td', 'tp', 'stage', 'projet']); // Type de cours
            
            // Période d'affectation
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->enum('statut', ['active', 'terminee', 'annulee', 'suspendue'])->default('active');
            
            // Évaluation de l'affectation
            $table->decimal('note_performance', 3, 2)->nullable();
            $table->text('commentaires')->nullable();
            $table->json('feedback_etudiants')->nullable(); // Retours des étudiants
            
            $table->foreignId('affecte_par')->constrained('users')->onDelete('cascade');
            $table->datetime('date_affectation');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index pour optimisation
            $table->index(['enseignant_profile_id', 'statut'], 'idx_ens_affec_statut');
            $table->index(['planification_id'], 'idx_ens_affec_planif');
            $table->index(['date_debut', 'date_fin'], 'idx_ens_affec_dates');
            $table->index(['type_affectation'], 'idx_ens_affec_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('esbtp_enseignant_affectations');
        Schema::dropIfExists('esbtp_enseignant_disponibilites');
        Schema::dropIfExists('esbtp_enseignant_profiles');
    }
};