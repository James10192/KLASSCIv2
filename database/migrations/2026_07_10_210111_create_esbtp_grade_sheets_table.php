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
        Schema::create('esbtp_grade_sheets', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('obligation_key', 64)->unique();
            $table->foreignId('evaluation_id')->nullable()->unique()
                ->constrained('esbtp_evaluations')->nullOnDelete();
            $table->foreignId('classe_id')->constrained('esbtp_classes')->onDelete('restrict');
            $table->foreignId('matiere_id')->constrained('esbtp_matieres')->onDelete('restrict');
            $table->foreignId('annee_universitaire_id')
                ->constrained('esbtp_annee_universitaires')->onDelete('restrict');
            $table->foreignId('teacher_id')->nullable()
                ->constrained('esbtp_teachers')->nullOnDelete();
            $table->foreignId('assigned_processor_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->string('academic_system', 12)->default('BTS');
            $table->string('semester', 20);
            $table->string('evaluation_type', 50);
            $table->string('entry_mode', 20)->default('direct');
            $table->string('status', 32)->default('expected');
            $table->dateTime('expected_at')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('received_at')->nullable();
            $table->dateTime('entry_started_at')->nullable();
            $table->dateTime('entered_at')->nullable();
            $table->dateTime('controlled_at')->nullable();
            $table->dateTime('validated_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('controlled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source', 32)->default('manual');
            $table->text('observations')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedInteger('lock_version')->default(1);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(
                ['annee_universitaire_id', 'semester', 'classe_id', 'status'],
                'egs_scope_status_idx'
            );
            $table->index(['teacher_id', 'status', 'expected_at'], 'egs_teacher_due_idx');
            $table->index(
                ['assigned_processor_id', 'status', 'expected_at'],
                'egs_processor_due_idx'
            );
            $table->index(['entry_mode', 'status'], 'egs_mode_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_grade_sheets');
    }
};
