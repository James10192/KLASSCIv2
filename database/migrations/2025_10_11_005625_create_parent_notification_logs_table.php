<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table de tracking des notifications parents multi-canal
     * Permet analyse ROI, debugging, et statistiques d'envoi
     */
    public function up(): void
    {
        Schema::create('parent_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('esbtp_parents')->onDelete('cascade');
            $table->unsignedBigInteger('etudiant_id')->nullable();
            $table->foreign('etudiant_id')->references('id')->on('esbtp_etudiants')->onDelete('cascade');

            // Type de notification
            $table->enum('notification_type', [
                'inscription',
                'reinscription',
                'paiement_valide',
                'paiement_rejete',
                'absence',
                'bulletin_publie',
                'notes_faibles',
                'annonce',
            ])->comment('Type de notification envoyée');

            // Canal utilisé
            $table->enum('channel', ['app', 'email', 'whatsapp', 'sms'])
                  ->comment('Canal de notification utilisé');

            // Statut d'envoi
            $table->enum('status', [
                'pending',    // En attente d'envoi
                'sent',       // Envoyé avec succès
                'delivered',  // Livré (webhook WhatsApp/SMS)
                'read',       // Lu par le destinataire (webhook)
                'failed',     // Échec d'envoi
            ])->default('pending');

            // Détails d'envoi
            $table->string('recipient')->nullable()->comment('Email/Téléphone destinataire');
            $table->text('message_preview')->nullable()->comment('Aperçu du message (100 premiers caractères)');
            $table->string('external_id')->nullable()->comment('ID message externe (WhatsApp/SMS message_id)');

            // Coût et métadonnées
            $table->decimal('cost_fcfa', 10, 2)->default(0)->comment('Coût en FCFA (0 pour app/email, ~3 pour WhatsApp, ~7 pour SMS)');
            $table->json('metadata')->nullable()->comment('Métadonnées supplémentaires (payload, erreur, etc.)');

            // Timestamps d'événements
            $table->timestamp('sent_at')->nullable()->comment('Date d\'envoi');
            $table->timestamp('delivered_at')->nullable()->comment('Date de livraison');
            $table->timestamp('read_at')->nullable()->comment('Date de lecture');
            $table->timestamp('failed_at')->nullable()->comment('Date d\'échec');
            $table->text('error_message')->nullable()->comment('Message d\'erreur si échec');

            $table->timestamps();

            // Index pour performance
            $table->index('parent_id');
            $table->index('etudiant_id');
            $table->index('notification_type');
            $table->index('channel');
            $table->index('status');
            $table->index(['parent_id', 'channel'], 'pnl_parent_channel_idx');
            $table->index(['notification_type', 'status'], 'pnl_type_status_idx');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parent_notification_logs');
    }
};
