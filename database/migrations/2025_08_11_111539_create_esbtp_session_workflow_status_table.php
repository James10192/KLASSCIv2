<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEsbtpSessionWorkflowStatusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('esbtp_session_workflow_status', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seance_cours_id');
            $table->unsignedBigInteger('teacher_id');
            $table->boolean('attendance_signed')->default(false); // Émargement fait
            $table->boolean('call_start_done')->default(false); // Appel de début fait
            $table->boolean('call_end_done')->default(false); // Appel de fin fait
            $table->boolean('report_submitted')->default(false); // Rapport soumis
            $table->enum('current_step', ['attendance', 'call_start', 'call_end', 'report', 'completed'])->default('attendance');
            $table->timestamp('attendance_signed_at')->nullable();
            $table->timestamp('call_start_done_at')->nullable();
            $table->timestamp('call_end_done_at')->nullable();
            $table->timestamp('report_submitted_at')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('seance_cours_id')->references('id')->on('esbtp_seance_cours')->onDelete('cascade');
            $table->foreign('teacher_id')->references('id')->on('users')->onDelete('cascade');
            
            // Index unique pour éviter les doublons
            $table->unique(['seance_cours_id', 'teacher_id']);
            $table->index('current_step');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_session_workflow_status');
    }
}
