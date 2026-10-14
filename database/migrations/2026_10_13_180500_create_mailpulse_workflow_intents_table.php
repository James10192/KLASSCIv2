<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mailpulse_workflow_intents', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('event', 80);
            // Encrypted at rest: attendance and school events can contain PII.
            $table->longText('payload_ciphertext');
            $table->string('status', 20)->default('queued');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('next_attempt_at')->nullable();
            $table->timestamp('lease_until')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->index(['status', 'next_attempt_at'], 'mailpulse_intents_due_idx');
            $table->index(['status', 'lease_until'], 'mailpulse_intents_lease_idx');
            $table->index(['event', 'created_at'], 'mailpulse_intents_event_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailpulse_workflow_intents');
    }
};
