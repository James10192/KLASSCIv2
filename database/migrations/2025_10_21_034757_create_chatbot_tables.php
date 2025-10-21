<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Système Chatbot KLASSCI avec Gemini AI
     */
    public function up(): void
    {
        // Table 1: Conversations
        Schema::create('chatbot_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('session_id')->unique();
            $table->string('title')->nullable()->comment('Auto-généré par IA');
            $table->json('context')->nullable()->comment('Contexte accumulé de la conversation');
            $table->timestamp('last_activity_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'is_active']);
            $table->index('session_id');
        });

        // Table 2: Messages
        Schema::create('chatbot_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('chatbot_conversations')->cascadeOnDelete();
            $table->enum('role', ['user', 'assistant', 'system'])->default('user');
            $table->text('content');
            $table->json('metadata')->nullable()->comment('Fonctions appelées, templates utilisés, etc.');
            $table->string('display_type')->nullable()->comment('text|table|card|kpi');
            $table->json('display_data')->nullable()->comment('Données pour le template');
            $table->string('deep_link')->nullable()->comment('URL vers page complète');
            $table->timestamps();

            $table->index('conversation_id');
            $table->index('role');
        });

        // Table 3: Actions Log (Audit trail)
        Schema::create('chatbot_actions_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('chatbot_conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->string('action_type')->comment('retrieve|create|update|delete');
            $table->string('model_type')->nullable()->comment('ESBTPPaiement, ESBTPEtudiant, etc.');
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('action_data')->nullable()->comment('Paramètres de l\'action');
            $table->enum('status', ['success', 'failed', 'pending'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'action_type']);
            $table->index(['conversation_id', 'status']);
        });

        // Table 4: System Prompts (Pre-prompts configurables)
        Schema::create('chatbot_system_prompts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Nom du prompt (ex: default, enseignant, coordinateur)');
            $table->text('prompt')->comment('Texte du system prompt');
            $table->json('allowed_roles')->nullable()->comment('Roles Spatie autorisés à utiliser ce prompt');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('priority')->default(0)->comment('Ordre d\'application (plus haut = prioritaire)');
            $table->timestamps();

            $table->index(['is_active', 'priority']);
        });

        // Table 5: Display Templates (Templates d'affichage)
        Schema::create('chatbot_display_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Nom du template (ex: paiements_table, kpi_card)');
            $table->string('type')->comment('table|card|kpi|chart');
            $table->text('description')->nullable();
            $table->text('html_template')->comment('Template HTML avec placeholders {{field}}');
            $table->json('required_fields')->comment('Champs requis dans display_data');
            $table->json('optional_fields')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['type', 'is_active']);
        });

        // Table 6: Knowledge Base (Mémoire d'exploration autonome)
        Schema::create('chatbot_knowledge_base', function (Blueprint $table) {
            $table->id();
            $table->string('intent')->unique()->comment('get_paiements, get_etudiants, etc.');
            $table->string('route')->nullable()->comment('Route Laravel (ex: esbtp.paiements.index)');
            $table->string('controller')->nullable()->comment('Nom du controller');
            $table->string('model')->nullable()->comment('Nom du modèle Eloquent');
            $table->string('table_name')->nullable()->comment('Nom de la table BDD');
            $table->json('columns_mapping')->nullable()->comment('Mapping colonnes {"statut": "column", "month": "date_field"}');
            $table->json('display_columns')->nullable()->comment('Colonnes à afficher dans le chat');
            $table->string('deep_link_pattern')->nullable()->comment('Pattern URL avec placeholders');
            $table->json('required_permissions')->nullable()->comment('Permissions Spatie requises');
            $table->json('allowed_roles')->nullable()->comment('Roles autorisés (depuis sidebar analysis)');
            $table->text('exploration_log')->nullable()->comment('Log de comment le chatbot a trouvé cette info');
            $table->timestamp('last_used_at')->nullable();
            $table->integer('usage_count')->default(0);
            $table->timestamps();

            $table->index('intent');
            $table->index(['last_used_at', 'usage_count']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatbot_knowledge_base');
        Schema::dropIfExists('chatbot_display_templates');
        Schema::dropIfExists('chatbot_system_prompts');
        Schema::dropIfExists('chatbot_actions_log');
        Schema::dropIfExists('chatbot_messages');
        Schema::dropIfExists('chatbot_conversations');
    }
};
