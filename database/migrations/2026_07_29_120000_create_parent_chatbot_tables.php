<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parent_chatbot_link_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('esbtp_parents')->cascadeOnDelete();
            $table->char('code_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['parent_id', 'expires_at']);
        });

        Schema::create('parent_chatbot_link_code_issuances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('esbtp_parents')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('parent_chatbot_link_code_id')->nullable();
            $table->foreign('parent_chatbot_link_code_id', 'pcli_link_code_fk')
                ->references('id')
                ->on('parent_chatbot_link_codes')
                ->nullOnDelete();
            $table->string('request_id', 100)->unique();
            $table->string('status', 32);
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->timestamp('attempted_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('error_code', 64)->nullable();
            $table->timestamps();

            $table->index(['parent_id', 'status']);
        });

        Schema::create('parent_chatbot_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('esbtp_parents')->cascadeOnDelete();
            $table->char('phone_hash', 64);
            $table->foreignId('selected_student_id')->nullable()->constrained('esbtp_etudiants')->nullOnDelete();
            $table->enum('status', ['active', 'stopped', 'revoked'])->default('active');
            $table->timestamp('last_inbound_at')->nullable();
            $table->timestamp('stopped_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->unique(['parent_id', 'phone_hash']);
            $table->index(['phone_hash', 'status']);
        });

        Schema::create('parent_chatbot_inbound_events', function (Blueprint $table) {
            $table->id();
            $table->string('source_event_id', 100)->unique();
            $table->char('payload_hash', 64);
            $table->string('outcome', 40)->nullable();
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_chatbot_inbound_events');
        Schema::dropIfExists('parent_chatbot_links');
        Schema::dropIfExists('parent_chatbot_link_code_issuances');
        Schema::dropIfExists('parent_chatbot_link_codes');
    }
};
