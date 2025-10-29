<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('esbtp_seance_cours', function (Blueprint $table) {
            if (!Schema::hasColumn('esbtp_seance_cours', 'homework_evaluation_id')) {
                $table->foreignId('homework_evaluation_id')
                    ->nullable()
                    ->after('homework_due_date')
                    ->constrained('esbtp_evaluations')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('esbtp_seance_cours', function (Blueprint $table) {
            if (Schema::hasColumn('esbtp_seance_cours', 'homework_evaluation_id')) {
                $table->dropForeign(['homework_evaluation_id']);
                $table->dropColumn('homework_evaluation_id');
            }
        });
    }
};
