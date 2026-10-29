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
    public function up()
    {
        Schema::create('esbtp_grade_sheet_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_sheet_id')->constrained('esbtp_grade_sheets')->restrictOnDelete();
            $table->foreignId('parent_revision_id')->nullable()->constrained('esbtp_grade_sheet_revisions')->restrictOnDelete();
            $table->unsignedInteger('revision_number');
            $table->unsignedInteger('source_lock_version');
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('validated_at');
            $table->text('reason')->nullable();
            $table->string('rules_version', 64);
            $table->json('snapshot');
            $table->string('snapshot_sha256', 64);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['grade_sheet_id', 'revision_number'], 'egsr_sheet_revision_unique');
            $table->unique(['grade_sheet_id', 'source_lock_version'], 'egsr_sheet_source_version_unique');
            $table->unique(['grade_sheet_id', 'snapshot_sha256'], 'egsr_sheet_hash_unique');
            $table->index(['grade_sheet_id', 'validated_at'], 'egsr_sheet_validated_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_grade_sheet_revisions');
    }
};
