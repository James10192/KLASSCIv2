<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_activation_dispatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('esbtp_candidature_workflows')->cascadeOnDelete();
            $table->string('channel', 16);
            $table->string('request_id', 191)->unique();
            $table->string('status', 24)->index();
            $table->string('provider_message_id', 191)->nullable();
            $table->string('provider_dispatch_state', 32)->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('error_code', 80)->nullable();
            $table->timestamps();

            $table->index(['workflow_id', 'created_at'], 'activation_dispatches_workflow_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_activation_dispatches');
    }
};
