<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateESBTPTeacherAvailabilitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('esbtp_teacher_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('esbtp_teachers')->onDelete('cascade');
            $table->integer('day_of_week'); // 0 = Lundi, 1 = Mardi, etc.
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('availability_type', ['available', 'preferred', 'unavailable'])->default('unavailable');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Contrainte unique pour éviter les doublons
            $table->unique(['teacher_id', 'day_of_week', 'start_time'], 'teacher_availability_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_teacher_availabilities');
    }
}
