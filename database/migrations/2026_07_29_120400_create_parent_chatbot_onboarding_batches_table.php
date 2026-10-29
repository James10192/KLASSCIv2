<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parent_chatbot_onboarding_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 24);
            $table->unsignedTinyInteger('processing_slot')->nullable()->unique();
            $table->unsignedInteger('total_count')->default(0);
            $table->unsignedInteger('pending_count')->default(0);
            $table->unsignedInteger('accepted_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('parent_chatbot_onboarding_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('batch_id')->constrained('parent_chatbot_onboarding_batches')->cascadeOnDelete();
            $table->foreignId('parent_id')->constrained('esbtp_parents')->cascadeOnDelete();
            $table->foreignId('issuance_id')->nullable()->constrained('parent_chatbot_link_code_issuances')->nullOnDelete();
            $table->string('request_id', 100)->unique();
            $table->string('status', 24);
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->timestamp('attempted_at')->nullable();
            $table->string('error_code', 64)->nullable();
            $table->string('lease_token', 64)->nullable();
            $table->timestamp('lease_expires_at')->nullable();
            $table->timestamps();

            $table->unique(['batch_id', 'parent_id']);
            $table->index(['batch_id', 'status', 'lease_expires_at'], 'parent_chatbot_onboarding_claim_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_chatbot_onboarding_items');
        Schema::dropIfExists('parent_chatbot_onboarding_batches');
    }
};
