<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esbtp_family_account_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grant_id')->constrained('esbtp_family_access_grants')->cascadeOnDelete();
            $table->foreignId('parent_id')->constrained('esbtp_parents')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('issued_by')->constrained('users')->restrictOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->string('request_id', 191)->unique();
            $table->string('recipient_hash', 64);
            $table->longText('encrypted_url')->nullable();
            $table->string('status', 24)->default('queued')->index();
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->timestamp('next_attempt_at')->nullable();
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('provider_message_id', 191)->nullable();
            $table->string('error_code', 80)->nullable();
            $table->timestamps();
            $table->index(['status', 'next_attempt_at'], 'family_invite_due_idx');
            $table->index(['grant_id', 'used_at'], 'family_invite_grant_used_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esbtp_family_account_invitations');
    }
};
