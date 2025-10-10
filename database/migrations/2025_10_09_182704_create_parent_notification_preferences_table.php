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
        Schema::create('parent_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('esbtp_parents')->onDelete('cascade');

            // Préférences par type d'événement
            $table->boolean('notify_inscriptions')->default(true)->comment('Recevoir notifications inscriptions');
            $table->boolean('notify_paiements')->default(true)->comment('Recevoir notifications paiements');
            $table->boolean('notify_absences')->default(true)->comment('Recevoir notifications absences');
            $table->boolean('notify_notes')->default(true)->comment('Recevoir notifications notes/évaluations');
            $table->boolean('notify_bulletins')->default(true)->comment('Recevoir notifications bulletins');
            $table->boolean('notify_annonces')->default(true)->comment('Recevoir annonces générales');

            // Canaux préférés (JSON array: ['email', 'whatsapp', 'sms', 'app'])
            $table->json('preferred_channels')->default('["app", "email"]')->comment('Canaux de notification préférés');

            // Seuils personnalisés pour alertes
            $table->integer('absence_threshold')->default(3)->comment('Seuil d\'alerte absences (nombre)');
            $table->decimal('grade_threshold', 3, 1)->default(10.0)->comment('Seuil d\'alerte moyennes (note sur 20)');
            $table->integer('attendance_rate_threshold')->default(80)->comment('Seuil taux de présence (pourcentage)');

            // Fréquence des rappels
            $table->enum('reminder_frequency', ['immediate', 'daily', 'weekly', 'never'])->default('immediate')
                  ->comment('Fréquence des rappels de paiement');

            // Langue préférée
            $table->string('preferred_language', 5)->default('fr')->comment('Langue des notifications (fr, en)');

            // Métadonnées
            $table->timestamp('last_notification_sent_at')->nullable()->comment('Date dernière notification envoyée');
            $table->integer('notifications_sent_count')->default(0)->comment('Nombre total de notifications envoyées');

            $table->timestamps();

            // Index pour performance
            $table->index('parent_id');
            $table->index(['notify_absences', 'notify_paiements'], 'pnp_absences_paiements_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parent_notification_preferences');
    }
};
