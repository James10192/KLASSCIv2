<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration Phase 7 Plan v4 — réponses sortantes manuelles (chat 2-way).
 *
 * Trace les réponses envoyées par les agents école (secrétaire, superAdmin) aux
 * messages entrants. Permet audit + statistiques temps de réponse + KPI satisfaction.
 *
 * Fenêtre 24h Meta : si reply hors fenêtre service window → utiliser un template
 * pré-approuvé sinon Meta refuse l'envoi.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('whatsapp_outbound_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inbound_message_id')->constrained('whatsapp_inbound_messages')->cascadeOnDelete();
            $table->foreignId('sent_by_user_id')->constrained('users')->restrictOnDelete();
            $table->text('body');
            $table->string('meta_message_id', 100)->nullable()->index();
            $table->enum('type', ['text', 'template'])->default('text');
            $table->string('template_name', 100)->nullable()->comment('Si type=template (hors fenêtre 24h)');
            $table->enum('status', ['pending', 'sent', 'delivered', 'read', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->decimal('cost_fcfa', 8, 2)->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_outbound_replies');
    }
};
