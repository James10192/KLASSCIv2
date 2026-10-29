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
        Schema::create('esbtp_grade_sheet_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_sheet_id')
                ->constrained('esbtp_grade_sheets')->onDelete('restrict');
            $table->string('disk', 32)->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size_bytes');
            $table->string('checksum_sha256', 64);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('uploaded_at');

            $table->unique(['disk', 'path'], 'egsd_disk_path_unique');
            $table->index(['grade_sheet_id', 'uploaded_at'], 'egsd_sheet_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_grade_sheet_documents');
    }
};
