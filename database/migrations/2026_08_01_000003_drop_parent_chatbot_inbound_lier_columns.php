<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parent_chatbot_inbound_events', function (Blueprint $table): void {
            $table->dropColumn([
                'lier_command',
                'lier_intent',
                'lier_side_effect_outcome',
                'lier_reply_ciphertext',
                'lier_recorded_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('parent_chatbot_inbound_events', function (Blueprint $table): void {
            $table->string('lier_command', 20)->nullable();
            $table->string('lier_intent', 64)->nullable();
            $table->string('lier_side_effect_outcome', 64)->nullable();
            $table->text('lier_reply_ciphertext')->nullable();
            $table->timestamp('lier_recorded_at')->nullable();
        });
    }
};
