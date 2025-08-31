<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Cette migration regroupe tout le système étudiant :
     * - Étudiants et parents
     * - Inscriptions et réinscriptions  
     * - Gestion des abandons
     */
    public function up(): void
    {
        // Parents/Tuteurs
        Schema::create('esbtp_parents', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('prenoms');
            $table->enum('relation', ['Père', 'Mère', 'Tuteur', 'Tutrice', 'Autre']);
            $table->string('telephone')->nullable();
            $table->string('email')->nullable();
            $table->string('profession')->nullable();
            $table->text('adresse')->nullable();
            $table->string('ville')->nullable();
            $table->timestamps();
            
            $table->index(['nom', 'prenoms']);
        });

        // Étudiants (avec toutes les colonnes fusionnées)
        Schema::create('esbtp_etudiants', function (Blueprint $table) {
            $table->id();
            $table->string('matricule')->unique();
            $table->string('nom');
            $table->string('prenoms');
            $table->enum('sexe', ['M', 'F']);
            $table->date('date_naissance')->nullable();
            $table->string('lieu_naissance')->nullable();
            $table->string('ville_naissance')->nullable();
            $table->string('commune_naissance')->nullable();
            
            // Coordonnées (compatibilité modèle existant)
            $table->string('telephone')->nullable();
            $table->string('email')->nullable(); // Ajout pour le nouveau Excel
            $table->string('email_personnel')->nullable(); // Existant dans le modèle
            $table->text('adresse')->nullable();
            $table->string('ville')->nullable();
            $table->string('commune')->nullable();
            
            // Relations familiales
            $table->foreignId('parent_id')->nullable()->constrained('esbtp_parents')->onDelete('set null');
            
            // Informations académiques
            $table->string('numero_bac')->nullable();
            $table->year('annee_bac')->nullable();
            $table->string('serie_bac')->nullable();
            $table->string('mention_bac')->nullable();
            $table->string('etablissement_origine')->nullable();
            
            // Statut et suivi
            $table->enum('statut', ['Actif', 'Inactif', 'Suspendu', 'Diplômé'])->default('Actif');
            $table->boolean('is_boursier')->default(false);
            $table->boolean('is_redoublant')->default(false);
            
            // Gestion des abandons (compatibilité modèle existant)
            $table->boolean('has_abandoned')->default(false);
            $table->date('date_abandon')->nullable(); // Nom utilisé dans le modèle
            $table->text('motif_abandon')->nullable(); // Nom utilisé dans le modèle
            $table->string('abandon_type')->nullable(); // Nom utilisé dans le modèle
            $table->text('abandon_comment')->nullable();
            
            // Champs additionnels du modèle existant
            $table->string('nationalite')->nullable();
            $table->string('photo')->nullable();
            $table->string('groupe_sanguin')->nullable();
            $table->string('situation_matrimoniale')->nullable();
            $table->integer('nombre_enfants')->nullable();
            $table->string('urgence_contact_nom')->nullable();
            $table->string('urgence_contact_telephone')->nullable();
            $table->string('urgence_contact_relation')->nullable();
            
            // Relations avec le système
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Audit (compatibilité modèle existant)
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            
            $table->softDeletes(); // Le modèle utilise SoftDeletes
            $table->timestamps();
            
            $table->index(['statut', 'has_abandoned']);
            $table->index(['nom', 'prenoms']);
            $table->index('matricule');
        });

        // Inscriptions (avec workflow fusionné)
        Schema::create('esbtp_inscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etudiant_id')->constrained('esbtp_etudiants')->onDelete('cascade');
            $table->foreignId('classe_id')->constrained('esbtp_classes')->onDelete('cascade');
            $table->foreignId('annee_universitaire_id')->constrained('esbtp_annee_universitaires')->onDelete('cascade');
            $table->date('date_inscription');
            $table->decimal('montant_inscription', 10, 2)->default(0);
            $table->enum('type_inscription', ['Première inscription', 'Réinscription', 'Transfert']);
            
            // Statut de l'inscription
            $table->enum('statut', ['En attente', 'Validée', 'Rejetée', 'Annulée'])->default('En attente');
            $table->text('commentaires')->nullable();
            
            // Workflow de validation (fusionné)
            $table->enum('workflow_status', ['draft', 'submitted', 'reviewed', 'approved', 'rejected'])->default('draft');
            $table->json('workflow_data')->nullable();
            $table->timestamp('workflow_updated_at')->nullable();
            
            // Réinscription (fusionné)
            $table->enum('reinscription_status', ['non_applicable', 'en_attente', 'confirmee', 'refusee'])->default('non_applicable');
            $table->date('reinscription_date')->nullable();
            $table->text('reinscription_notes')->nullable();
            
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['statut', 'workflow_status']);
            $table->index(['etudiant_id', 'annee_universitaire_id']);
        });

        // Historique du workflow d'inscription (fusionné)
        Schema::create('esbtp_inscription_workflow_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscription_id')->constrained('esbtp_inscriptions')->onDelete('cascade');
            $table->string('from_status');
            $table->string('to_status');
            $table->text('comment')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('created_at');
            
            $table->index(['inscription_id', 'created_at'], 'idx_inscription_workflow_history');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('esbtp_inscription_workflow_history');
        Schema::dropIfExists('esbtp_inscriptions');
        Schema::dropIfExists('esbtp_etudiants');
        Schema::dropIfExists('esbtp_parents');
    }
};