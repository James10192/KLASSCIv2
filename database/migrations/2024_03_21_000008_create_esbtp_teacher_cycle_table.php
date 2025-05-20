<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateESBTPTeacherCycleTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('esbtp_teacher_cycle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('esbtp_teachers')->onDelete('cascade');
            $table->foreignId('cycle_id')->constrained('esbtp_cycles')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['teacher_id', 'cycle_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('esbtp_teacher_cycle');
    }
}
