<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEsbtpPlanificationTeachersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('esbtp_planification_teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planification_id')->constrained('esbtp_planifications_academiques')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('esbtp_teachers')->onDelete('cascade');
            $table->timestamps();
            
            // Contrainte unique pour éviter les doublons
            $table->unique(['planification_id', 'teacher_id'], 'planification_teacher_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_planification_teachers');
    }
}
