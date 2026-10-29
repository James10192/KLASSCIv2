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
        Schema::create('esbtp_grade_sheet_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_sheet_id')->constrained('esbtp_grade_sheets')->cascadeOnDelete();
            $table->foreignId('etudiant_id')->constrained('esbtp_etudiants')->onDelete('restrict');
            $table->foreignId('note_id')->nullable()->unique()
                ->constrained('esbtp_notes')->nullOnDelete();
            $table->string('status', 24)->default('expected');
            $table->string('source', 32)->default('workflow');
            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('resolved_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['grade_sheet_id', 'etudiant_id'], 'egse_sheet_student_unique');
            $table->index(['grade_sheet_id', 'status'], 'egse_sheet_status_idx');
            $table->index(['etudiant_id', 'status'], 'egse_student_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_grade_sheet_entries');
    }
};
