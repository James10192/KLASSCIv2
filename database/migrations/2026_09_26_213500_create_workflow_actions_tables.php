<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workflow_actions', function (Blueprint $table) {
            $table->id();
            $table->string('action_type', 80);
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->enum('status', ['todo', 'in_progress', 'waiting_info', 'done', 'rejected'])->default('todo');
            $table->string('service', 120)->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('chat_conversation_id')->nullable()->constrained('chat_conversations')->nullOnDelete();
            $table->string('context_type', 120)->nullable();
            $table->unsignedBigInteger('context_id')->nullable();
            $table->json('context_data')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'priority']);
            $table->index(['assigned_to', 'status']);
            $table->index(['context_type', 'context_id']);
        });

        Schema::create('workflow_action_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_action_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 80);
            $table->text('comment')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['workflow_action_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_action_activities');
        Schema::dropIfExists('workflow_actions');
    }
};
