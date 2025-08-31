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
        // Vérifier si la table existe déjà
        $tableExists = Schema::hasTable('esbtp_etudiants');
        
        // Si la table n'existe pas, la créer
        if (!$tableExists) {
            Schema::create('esbtp_etudiants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
                $table->string('matricule')->unique();
                $table->string('nom');
                $table->string('prenoms');
                $table->enum('sexe', ['M', 'F']);
                $table->date('date_naissance')->nullable();
                $table->string('lieu_naissance')->nullable();
                $table->string('nationalite')->nullable();
                $table->text('adresse')->nullable();
                $table->string('telephone')->nullable();
                $table->string('email_personnel')->nullable();
                $table->string('photo')->nullable();
                $table->enum('statut', ['actif', 'inactif', 'diplômé', 'abandon', 'exclu'])->default('actif');
                $table->string('groupe_sanguin')->nullable();
                $table->string('situation_matrimoniale')->nullable();
                $table->integer('nombre_enfants')->default(0);
                $table->string('urgence_contact_nom')->nullable();
                $table->string('urgence_contact_telephone')->nullable();
                $table->string('urgence_contact_relation')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
                $table->softDeletes();
            });
            
            // Journalisation de la création de la table
            \Log::info('Table esbtp_etudiants créée avec succès.');
        } else {
            // Journalisation de l'existence de la table
            \Log::info('La table esbtp_etudiants existe déjà.');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('esbtp_etudiants');
    }
};