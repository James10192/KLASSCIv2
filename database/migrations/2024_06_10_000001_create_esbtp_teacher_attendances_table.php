<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('esbtp_teacher_attendances')) {
            Schema::create('esbtp_teacher_attendances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('course_id')->constrained('esbtp_courses')->onDelete('cascade');
                $table->foreignId('daily_code_id')->constrained('esbtp_daily_codes')->onDelete('cascade');
                $table->date('date');
                $table->string('status')->index();
                $table->integer('attempts')->default(0);
                $table->dateTime('validated_at')->nullable();
                $table->json('device_info')->nullable();
                $table->json('geolocation_data')->nullable();
                $table->string('ip_address')->nullable();
                $table->timestamps();
                $table->unique(['teacher_id', 'course_id', 'date'], 'uniq_teacher_course_date');
                $table->unsignedBigInteger('daily_code_id')->nullable()->change();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('esbtp_teacher_attendances');
    }
};
