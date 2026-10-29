<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('chatbot_knowledge_base', function (Blueprint $table) {
            // Hiérarchie de navigation pour exploration multi-niveaux
            // Ex: {"level_1": "esbtp.frais.index", "level_2": "esbtp.frais.configure"}
            $table->json('navigation_hierarchy')->nullable()->after('exploration_log');

            // Intent parent pour navigation inversée
            // Ex: get_frais_scolarite_bts_batiment -> parent: get_frais
            $table->string('parent_intent')->nullable()->after('intent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chatbot_knowledge_base', function (Blueprint $table) {
            $table->dropColumn(['navigation_hierarchy', 'parent_intent']);
        });
    }
};
