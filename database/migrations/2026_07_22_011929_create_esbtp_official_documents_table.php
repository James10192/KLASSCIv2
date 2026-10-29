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
        Schema::create('esbtp_official_documents', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 64);
            $table->string('source_type', 191);
            $table->unsignedBigInteger('source_id');
            $table->string('series_key', 191);
            $table->unsignedInteger('version');
            $table->string('reference', 96)->unique();
            $table->string('status', 16)->default('valid');
            $table->string('valid_series_key', 191)->nullable()->unique();
            $table->string('disk', 32)->default('local');
            $table->string('path');
            $table->string('original_name', 255);
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size_bytes');
            $table->string('checksum_sha256', 64);
            $table->json('snapshot')->nullable();
            $table->string('snapshot_sha256', 64)->nullable();
            $table->string('rules_version', 64)->nullable();
            $table->string('template_version', 64)->nullable();
            $table->string('renderer_version', 64)->nullable();
            $table->string('verification_code_digest', 64)->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('issued_at');
            $table->foreignId('supersedes_document_id')->nullable()
                ->constrained('esbtp_official_documents')->restrictOnDelete();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('revoked_at')->nullable();
            $table->text('revocation_reason')->nullable();
            $table->json('lifecycle_metadata')->nullable();
            $table->timestamps();

            $table->unique(['series_key', 'version'], 'eod_series_version_unique');
            $table->unique('supersedes_document_id', 'eod_supersedes_unique');
            $table->unique(['disk', 'path'], 'eod_disk_path_unique');
            $table->index(['source_type', 'source_id', 'document_type'], 'eod_source_type_idx');
            $table->index(['series_key', 'status'], 'eod_series_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_official_documents');
    }
};
