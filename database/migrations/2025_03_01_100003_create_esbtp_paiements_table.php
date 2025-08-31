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
        $tableExists = Schema::hasTable('esbtp_paiements');

        // Si la table n'existe pas, la créer
        if (!$tableExists) {
            Schema::create('esbtp_paiements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inscription_id')->constrained('esbtp_inscriptions')->onDelete('cascade');
                $table->foreignId('etudiant_id')->constrained('esbtp_etudiants')->onDelete('cascade');
                $table->foreignId('annee_universitaire_id')->constrained('esbtp_annee_universitaires')->onDelete('restrict');
                $table->string('type_paiement', 100);
                $table->decimal('montant', 10, 2);
                $table->date('date_paiement');
                $table->date('date_echeance')->nullable();
                $table->string('mode_paiement')->comment('Espèces, chèque, virement, etc.');
                $table->string('numero_transaction')->nullable()->comment('Numéro de chèque, de transaction, etc.');
                $table->string('reference_paiement')->nullable()->comment('Numéro de chèque, de transaction, etc.');
                $table->string('tranche')->nullable()->comment('Première tranche, deuxième tranche, etc.');
                $table->string('motif')->comment('Scolarité, frais d\'inscription, frais divers, etc.');
                $table->string('numero_recu');
                $table->text('commentaire')->nullable();
                $table->enum('status', ['en_attente', 'validé', 'rejeté'])->default('en_attente');
                $table->date('date_validation')->nullable();
                $table->foreignId('validated_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
                $table->softDeletes();

                // Index pour accélérer les recherches
                $table->index(['etudiant_id', 'inscription_id']);
                $table->index(['numero_recu']);
                $table->index(['date_paiement']);
                $table->index(['status']);
            });

            // Journalisation de la création de la table
            \Log::info('Table esbtp_paiements créée avec succès.');
        } else {
            // Journalisation de l'existence de la table
            \Log::info('La table esbtp_paiements existe déjà.');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('esbtp_paiements');
    }
};
