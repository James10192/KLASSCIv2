<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Immutable attempt identity and append-only lifecycle events.
     * A delivery remains the one-recipient business aggregate.
     */
    public function up(): void
    {
        Schema::create('esbtp_annonce_email_attempts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('annonce_id');
            $table->unsignedBigInteger('delivery_id');
            $table->unsignedInteger('attempt_number');
            $table->string('external_reference', 191)->unique();
            $table->string('previous_reference', 191);
            $table->string('recipient_email', 254);
            $table->string('status', 32)->default('queued');
            $table->string('provider_message_id', 191)->nullable();
            $table->string('error_code', 64)->nullable();
            $table->unsignedBigInteger('requested_by');
            $table->timestamp('dispatch_started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->unique(['delivery_id', 'attempt_number'], 'annonce_attempt_delivery_number_uq');
            $table->index(['annonce_id', 'status', 'created_at'], 'annonce_attempt_campaign_status_idx');
            $table->foreign('delivery_id', 'annonce_attempt_delivery_fk')
                ->references('id')->on('esbtp_annonce_email_deliveries')->cascadeOnDelete();
            $table->foreign('annonce_id', 'annonce_attempt_annonce_fk')
                ->references('id')->on('esbtp_annonces')->cascadeOnDelete();
        });

        Schema::create('esbtp_annonce_email_attempt_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attempt_id');
            $table->string('event', 48);
            $table->string('detail_code', 64)->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('attempt_id', 'annonce_attempt_events_attempt_fk')
                ->references('id')->on('esbtp_annonce_email_attempts')->cascadeOnDelete();
            $table->index(['attempt_id', 'created_at'], 'annonce_attempt_event_timeline_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esbtp_annonce_email_attempt_events');
        Schema::dropIfExists('esbtp_annonce_email_attempts');
    }
};
