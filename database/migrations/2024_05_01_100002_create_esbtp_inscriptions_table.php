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
        $tableExists = Schema::hasTable('esbtp_inscriptions');
        
        // Si la table n'existe pas, la créer
        if (!$tableExists) {
            Schema::create('esbtp_inscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('etudiant_id')->constrained('esbtp_etudiants')->onDelete('cascade');
                $table->foreignId('annee_universitaire_id')->constrained('esbtp_annee_universitaires')->onDelete('restrict');
                $table->foreignId('filiere_id')->constrained('esbtp_filieres')->onDelete('restrict');
                $table->foreignId('niveau_id')->constrained('esbtp_niveau_etudes')->onDelete('restrict');
                $table->foreignId('classe_id')->nullable()->constrained('esbtp_classes')->onDelete('set null');
                $table->date('date_inscription');
                $table->enum('type_inscription', ['première_inscription', 'réinscription', 'transfert'])->default('première_inscription');
                $table->enum('status', ['en_attente', 'active', 'annulée', 'terminée'])->default('en_attente');
                $table->decimal('montant_scolarite', 10, 2);
                $table->decimal('frais_inscription', 10, 2);
                $table->string('numero_recu')->nullable();
                $table->date('date_paiement')->nullable();
                $table->string('mode_paiement')->nullable();
                $table->text('observations')->nullable();
                $table->json('documents_fournis')->nullable();
                $table->date('date_validation')->nullable();
                $table->foreignId('validated_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
                $table->softDeletes();

                // Un étudiant ne peut avoir qu'une seule inscription active par année universitaire
                $table->unique(['etudiant_id', 'annee_universitaire_id', 'status'], 'unique_active_inscription');
            });
            
            // Journalisation de la création de la table
            \Log::info('Table esbtp_inscriptions créée avec succès.');
        } else {
            // Journalisation de l'existence de la table
            \Log::info('La table esbtp_inscriptions existe déjà.');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('esbtp_inscriptions');
    }
}; 