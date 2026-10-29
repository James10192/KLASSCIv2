<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reconciliation_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique()->comment('REC-{ANNEE}-{SEQ4}');
            $table->enum('frequency', ['daily', 'weekly', 'monthly'])->default('daily');
            $table->unsignedBigInteger('annee_universitaire_id');
            $table->date('period_start');
            $table->date('period_end');
            $table->enum('status', ['draft', 'review', 'approved', 'closed', 'reopened'])->default('draft');

            $table->foreignId('opened_by')->constrained('users')->onDelete('restrict');
            $table->timestamp('opened_at');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('reopened_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reopened_at')->nullable();
            $table->text('reopen_reason')->nullable();

            $table->string('pv_pdf_path', 255)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['annee_universitaire_id', 'period_start', 'period_end'], 'rec_sessions_period_idx');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliation_sessions');
    }
};
