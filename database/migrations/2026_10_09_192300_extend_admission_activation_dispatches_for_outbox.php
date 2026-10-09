<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admission_activation_dispatches', function (Blueprint $table) {
            $table->longText('encrypted_payload')->nullable();
            $table->string('token_version', 16)->nullable();
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->timestamp('next_attempt_at')->nullable();
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->index(['status', 'next_attempt_at'], 'activation_outbox_due_idx');
        });
    }

    public function down(): void
    {
        Schema::table('admission_activation_dispatches', function (Blueprint $table) {
            $table->dropIndex('activation_outbox_due_idx');
            $table->dropColumn([
                'encrypted_payload', 'token_version', 'attempt_count',
                'next_attempt_at', 'locked_until', 'expires_at',
            ]);
        });
    }
};
