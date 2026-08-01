<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parent_chatbot_inbound_events', function (Blueprint $table) {
            $table->string('processing_token', 64)->nullable();
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('processing_expires_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('parent_chatbot_inbound_events', function (Blueprint $table) {
            $table->dropColumn([
                'processing_token',
                'processing_started_at',
                'processing_expires_at',
            ]);
        });
    }
};
