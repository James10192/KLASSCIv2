<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration Phase 7 Plan v4 — chat 2-way inbox WhatsApp.
 *
 * Stocke les messages entrants des parents (lookup par from_phone → parent → étudiant).
 * Permet à l'école de répondre via UI premium /esbtp/communications/whatsapp-inbox.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('whatsapp_inbound_messages', function (Blueprint $table) {
            $table->id();
            $table->string('message_id', 100)->unique()->comment('Meta wa_id pour idempotency');
            $table->string('from_phone', 20)->index()->comment('E.164 sans +');
            $table->string('to_number', 20)->nullable()->comment('Numéro WhatsApp du tenant');
            $table->foreignId('parent_id')->nullable()->constrained('esbtp_parents')->nullOnDelete();
            $table->foreignId('etudiant_id')->nullable()->constrained('esbtp_etudiants')->nullOnDelete();
            $table->enum('type', ['text', 'image', 'document', 'audio', 'video', 'location', 'other'])->default('text');
            $table->text('body')->nullable();
            $table->string('media_url', 500)->nullable();
            $table->json('raw_payload')->nullable()->comment('Full Meta webhook payload pour debug');
            $table->timestamp('received_at')->index();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('replied_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['unread', 'read', 'replied', 'archived'])->default('unread')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'received_at']);
            $table->index(['parent_id', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_inbound_messages');
    }
};
