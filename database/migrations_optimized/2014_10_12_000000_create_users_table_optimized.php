<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Cette migration regroupe toutes les modifications de la table users :
     * - Création de base
     * - Ajout des colonnes de profil
     * - Ajout des colonnes de suivi
     * - Soft deletes
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            
            // Colonnes de profil (fusionné de plusieurs migrations)
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('username')->nullable()->unique();
            $table->string('role')->nullable();
            $table->string('profile_photo_path', 2048)->nullable();
            
            // Informations de contact (fusionné)
            $table->string('telephone')->nullable();
            $table->string('adresse')->nullable();
            $table->string('ville')->nullable();
            $table->string('code_postal')->nullable();
            
            // Informations professionnelles (fusionné)
            $table->string('poste')->nullable();
            $table->string('departement')->nullable();
            $table->string('matricule_employe')->nullable();
            $table->date('date_embauche')->nullable();
            
            // Suivi des connexions et activité (fusionné)
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip')->nullable();
            $table->boolean('is_active')->default(true);
            
            // Gestion des mots de passe (fusionné)
            $table->boolean('must_change_password')->default(false);
            $table->timestamp('password_changed_at')->nullable();
            
            // Audit et suivi (fusionné) 
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            
            // Soft deletes (fusionné)
            $table->softDeletes();
            
            $table->rememberToken();
            $table->timestamps();
            
            // Index pour performance
            $table->index(['email', 'is_active']);
            $table->index(['role', 'is_active']);
            $table->index('username');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};