<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('esbtp_seance_cours', function (Blueprint $table) {
            // Add teacher_id column with foreign key constraint
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->foreign('teacher_id')
                  ->references('id')
                  ->on('esbtp_teachers')
                  ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('esbtp_seance_cours', function (Blueprint $table) {
            // Remove foreign key constraint and column
            $table->dropForeign(['teacher_id']);
            $table->dropColumn('teacher_id');
        });
    }
};
