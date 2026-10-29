<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('esbtp_reliquats_details', function (Blueprint $table) {
            $table->id();

            // Inscription source (année N) d'où vient le reliquat
            $table->foreignId('inscription_source_id')->constrained('esbtp_inscriptions')->onDelete('cascade');

            // Inscription destination (année N+1) qui hérite du reliquat
            $table->foreignId('inscription_destination_id')->constrained('esbtp_inscriptions')->onDelete('cascade');

            // Souscription de frais spécifique concernée
            $table->foreignId('frais_subscription_id')->constrained('esbtp_frais_subscriptions')->onDelete('cascade');

            // Montants détaillés
            $table->decimal('montant_attendu', 10, 2); // Montant original de la souscription
            $table->decimal('montant_paye', 10, 2); // Ce qui a été payé sur l'année N
            $table->decimal('montant_reliquat', 10, 2); // Ce qui reste à payer (reporté sur année N+1)
            $table->decimal('montant_regle', 10, 2)->default(0); // Ce qui a été payé via l'année N+1

            // Statut du reliquat
            $table->enum('statut', ['actif', 'partiellement_regle', 'totalement_regle'])->default('actif');

            // Traçabilité
            $table->timestamp('date_creation');
            $table->timestamp('date_derniere_maj')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->text('notes')->nullable();

            $table->timestamps();

            // Index pour les performances
            $table->index(['inscription_destination_id', 'statut']);
            $table->index(['frais_subscription_id']);
            $table->index('statut');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_reliquats_details');
    }
};
