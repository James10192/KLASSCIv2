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
        Schema::table('esbtp_seance_cours', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['enseignant_id']);

            // Drop the enseignant_id column
            $table->dropColumn('enseignant_id');

            // Add the new enseignant text column
            $table->string('enseignant')->nullable()->after('matiere_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('esbtp_seance_cours', function (Blueprint $table) {
            // Drop the enseignant text column
            $table->dropColumn('enseignant');

            // Add back the enseignant_id column with foreign key
            $table->foreignId('enseignant_id')->nullable()->after('matiere_id')->constrained('users')->onDelete('set null');
        });
    }
};
