<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('esbtp_notes', function (Blueprint $table) {
            if (! Schema::hasColumn('esbtp_notes', 'submission_status')) {
                $afterColumn = Schema::hasColumn('esbtp_notes', 'commentaire') ? 'commentaire' : 'is_absent';

                $table->string('submission_status', 24)
                    ->default('submitted')
                    ->after($afterColumn)
                    ->index('esbtp_notes_submission_status_index');
            }

            if (! Schema::hasColumn('esbtp_notes', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('submission_status');
            }

            if (! Schema::hasColumn('esbtp_notes', 'submitted_by')) {
                $table->foreignId('submitted_by')
                    ->nullable()
                    ->after('submitted_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        Schema::table('esbtp_notes', function (Blueprint $table) {
            $table->index(
                ['evaluation_id', 'etudiant_id', 'submission_status'],
                'esbtp_notes_eval_student_submission_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('esbtp_notes', function (Blueprint $table) {
            $table->dropIndex('esbtp_notes_eval_student_submission_index');

            if (Schema::hasColumn('esbtp_notes', 'submitted_by')) {
                $table->dropConstrainedForeignId('submitted_by');
            }

            if (Schema::hasColumn('esbtp_notes', 'submitted_at')) {
                $table->dropColumn('submitted_at');
            }

            if (Schema::hasColumn('esbtp_notes', 'submission_status')) {
                $table->dropIndex('esbtp_notes_submission_status_index');
                $table->dropColumn('submission_status');
            }
        });
    }
};
