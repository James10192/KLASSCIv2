<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateESBTPTeacherAttendanceTable extends Migration
{
    public function up()
    {
        Schema::create('esbtp_teacher_attendance', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id');
            $table->unsignedBigInteger('emploi_du_temps_id');
            $table->unsignedBigInteger('daily_code_id');
            $table->dateTime('signed_at');
            $table->string('ip_address', 45)->nullable();
            $table->json('geolocation_data')->nullable();
            $table->string('device_info')->nullable();
            $table->timestamps();

            $table->unique(['teacher_id', 'emploi_du_temps_id', 'daily_code_id'], 'teacher_attendance_unique');
            $table->index('signed_at');

            // We'll add the foreign key constraints after all tables are created
        });
    }

    public function down()
    {
        Schema::dropIfExists('esbtp_teacher_attendance');
    }
}
