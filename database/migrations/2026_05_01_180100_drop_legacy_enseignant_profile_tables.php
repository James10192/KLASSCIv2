<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fusion DB — Phase 2 : drop des 3 tables legacy.
     * Les données utiles ont été copiées dans `esbtp_teachers` par la migration
     * 2026_05_01_180000. Les colonnes non utilisées par l'UI moderne sont
     * abandonnées (specialites JSON, methodes_enseignement_preferees, etc.).
     *
     * Idempotent : `dropIfExists` ne lève pas si la table n'existe plus.
     * Ordre : enfants avant parents (FK dépendantes).
     */
    public function up(): void
    {
        Schema::dropIfExists('esbtp_enseignant_disponibilites');
        Schema::dropIfExists('esbtp_enseignant_affectations');
        Schema::dropIfExists('esbtp_enseignant_profiles');
    }

    /**
     * Reverse migration : recrée les tables vides (data perdue).
     * Best-effort pour permettre le rollback en dev. En prod, restaurer depuis backup.
     */
    public function down(): void
    {
        Schema::create('esbtp_enseignant_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('matricule_enseignant')->unique()->nullable();
            $table->string('titre_academique')->nullable();
            $table->string('grade_academique')->nullable();
            $table->string('diplome_principal')->nullable();
            $table->string('universite_diplome')->nullable();
            $table->year('annee_diplome')->nullable();
            $table->enum('type_contrat', ['permanent', 'temporaire', 'vacataire', 'consultant'])->default('vacataire');
            $table->enum('statut_emploi', ['temps_plein', 'temps_partiel', 'vacations'])->default('vacations');
            $table->decimal('taux_horaire', 10, 2)->nullable();
            $table->date('date_embauche')->nullable();
            $table->date('fin_contrat')->nullable();
            $table->integer('charge_horaire_max_semaine')->default(40);
            $table->string('statut')->default('actif');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('esbtp_enseignant_disponibilites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enseignant_profile_id')
                ->constrained('esbtp_enseignant_profiles')
                ->onDelete('cascade');
            $table->integer('jour_semaine');
            $table->time('heure_debut');
            $table->time('heure_fin');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('esbtp_enseignant_affectations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enseignant_profile_id')
                ->constrained('esbtp_enseignant_profiles')
                ->onDelete('cascade');
            $table->foreignId('matiere_id')
                ->constrained('esbtp_matieres')
                ->onDelete('cascade');
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
