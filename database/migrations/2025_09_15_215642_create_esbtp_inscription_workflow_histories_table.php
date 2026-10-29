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
        Schema::create('esbtp_inscription_workflow_histories', function (Blueprint $table) {
            $table->id();

            // Clé étrangère vers la table des inscriptions
            $table->unsignedBigInteger('inscription_id');
            $table->foreign('inscription_id')->references('id')->on('esbtp_inscriptions')->onDelete('cascade');

            // Étapes du workflow
            $table->string('etape_from')->nullable(); // Étape d'origine (peut être null pour la création)
            $table->string('etape_to'); // Étape de destination

            // Action effectuée
            $table->string('action'); // Ex: creation, validation, rejet, paiement_associe, etc.

            // Utilisateur qui a effectué l'action
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');

            // Timestamp de l'action (pas les timestamps Laravel classiques)
            $table->datetime('action_timestamp');

            // Commentaires optionnels
            $table->text('commentaires')->nullable();

            // Métadonnées additionnelles (JSON)
            $table->json('metadata')->nullable();

            // Informations de traçabilité
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            // Index pour améliorer les performances (avec noms courts)
            $table->index(['inscription_id', 'action_timestamp'], 'iwf_insc_timestamp_idx');
            $table->index('action', 'iwf_action_idx');
            $table->index('user_id', 'iwf_user_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_inscription_workflow_histories');
    }
};
