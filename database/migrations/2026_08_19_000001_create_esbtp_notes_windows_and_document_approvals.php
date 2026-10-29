<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('esbtp_notes_windows')) {
            Schema::create('esbtp_notes_windows', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('classe_id');
                $table->date('starts_at');
                $table->date('ends_at');
                $table->unsignedBigInteger('opened_by')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->unsignedBigInteger('closed_by')->nullable();
                $table->timestamps();

                $table->index(['classe_id', 'starts_at', 'ends_at']);
            });
        }

        if (! Schema::hasTable('esbtp_document_approvals')) {
            Schema::create('esbtp_document_approvals', function (Blueprint $table) {
                $table->id();
                $table->string('document_type', 40);
                $table->unsignedBigInteger('etudiant_id');
                $table->unsignedBigInteger('document_id')->nullable();
                $table->string('status', 20)->default('pending');
                $table->unsignedBigInteger('requested_by')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['document_type', 'etudiant_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('esbtp_document_approvals');
        Schema::dropIfExists('esbtp_notes_windows');
    }
};