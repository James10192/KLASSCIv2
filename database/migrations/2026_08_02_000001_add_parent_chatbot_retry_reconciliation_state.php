<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parent_chatbot_onboarding_items', function (Blueprint $table): void {
            $table->timestamp('next_attempt_at')->nullable()->after('attempted_at');
            $table->index(['status', 'next_attempt_at'], 'parent_chatbot_onboarding_due_idx');
        });

        Schema::table('parent_chatbot_onboarding_batches', function (Blueprint $table): void {
            $table->unsignedInteger('manual_reconciliation_count')->default(0)->after('failed_count');
        });

        Schema::table('parent_chatbot_link_code_issuances', function (Blueprint $table): void {
            $table->string('provider_command_id', 100)->nullable()->after('request_id');
            $table->timestamp('manual_reconciliation_at')->nullable()->after('failed_at');
        });
    }

    public function down(): void
    {
        Schema::table('parent_chatbot_link_code_issuances', function (Blueprint $table): void {
            $table->dropColumn(['provider_command_id', 'manual_reconciliation_at']);
        });
        Schema::table('parent_chatbot_onboarding_batches', function (Blueprint $table): void {
            $table->dropColumn('manual_reconciliation_count');
        });
        Schema::table('parent_chatbot_onboarding_items', function (Blueprint $table): void {
            $table->dropIndex('parent_chatbot_onboarding_due_idx');
            $table->dropColumn('next_attempt_at');
        });
    }
};
