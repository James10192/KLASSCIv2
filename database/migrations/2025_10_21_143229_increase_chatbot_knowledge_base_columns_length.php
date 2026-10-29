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
            // Passer de TEXT (65535 bytes) à MEDIUMTEXT (16MB)
            $table->mediumText('deep_link_pattern')->change();
            $table->mediumText('columns_mapping')->nullable()->change();
            $table->mediumText('display_columns')->nullable()->change();
            $table->mediumText('exploration_log')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chatbot_knowledge_base', function (Blueprint $table) {
            $table->text('deep_link_pattern')->change();
            $table->text('columns_mapping')->nullable()->change();
            $table->text('display_columns')->nullable()->change();
            $table->text('exploration_log')->nullable()->change();
        });
    }
};
