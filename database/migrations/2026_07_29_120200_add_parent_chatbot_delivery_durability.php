<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parent_chatbot_link_code_issuances', function (Blueprint $table): void {
            $table->string('status', 32)->change();
            $table->text('delivery_payload')->nullable()->after('error_code');
            $table->timestamp('delivery_payload_expires_at')->nullable()->after('delivery_payload');
            $table->string('delivery_token', 64)->nullable()->after('delivery_payload_expires_at');
            $table->timestamp('delivery_started_at')->nullable()->after('delivery_token');
            $table->timestamp('delivery_lease_expires_at')->nullable()->after('delivery_started_at');
            $table->timestamp('next_attempt_at')->nullable()->after('delivery_lease_expires_at');
            $table->timestamp('retry_expires_at')->nullable()->after('next_attempt_at');
            $table->index(['status', 'delivery_lease_expires_at'], 'parent_chatbot_issuance_delivery_lease_idx');
            $table->index(['status', 'next_attempt_at'], 'parent_chatbot_issuance_delivery_due_idx');
        });

        Schema::table('parent_chatbot_inbound_events', function (Blueprint $table): void {
            $table->string('lier_command', 20)->nullable()->after('processing_expires_at');
            $table->string('lier_intent', 64)->nullable()->after('lier_command');
            $table->string('lier_side_effect_outcome', 64)->nullable()->after('lier_intent');
            $table->text('lier_reply_ciphertext')->nullable()->after('lier_side_effect_outcome');
            $table->timestamp('lier_recorded_at')->nullable()->after('lier_reply_ciphertext');
            $table->text('response_ciphertext')->nullable()->after('lier_recorded_at');
            $table->timestamp('response_recorded_at')->nullable()->after('response_ciphertext');
        });
    }

    public function down(): void
    {
        Schema::table('parent_chatbot_inbound_events', function (Blueprint $table): void {
            $table->dropColumn([
                'lier_command',
                'lier_intent',
                'lier_side_effect_outcome',
                'lier_reply_ciphertext',
                'lier_recorded_at',
                'response_ciphertext',
                'response_recorded_at',
            ]);
        });

        Schema::table('parent_chatbot_link_code_issuances', function (Blueprint $table): void {
            $table->dropIndex('parent_chatbot_issuance_delivery_lease_idx');
            $table->dropIndex('parent_chatbot_issuance_delivery_due_idx');
            $table->dropColumn([
                'delivery_payload',
                'delivery_payload_expires_at',
                'delivery_token',
                'delivery_started_at',
                'delivery_lease_expires_at',
                'next_attempt_at',
                'retry_expires_at',
            ]);
        });
    }
};