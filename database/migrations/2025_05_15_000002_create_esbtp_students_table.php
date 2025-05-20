<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateESBTPStudentsTable extends Migration
{
    public function up()
    {
        Schema::create('esbtp_students', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('registration_number', 50)->unique();
            $table->foreignId('department_id')->nullable()->constrained('esbtp_departments')->onDelete('set null');
            $table->foreignId('cycle_id')->nullable()->constrained('esbtp_cycles')->onDelete('set null');
            $table->foreignId('class_id')->nullable()->constrained('esbtp_classes')->onDelete('set null');
            $table->date('date_of_birth');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->text('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->unique();
            $table->string('guardian_name');
            $table->string('guardian_phone', 20);
            $table->string('guardian_email')->nullable();
            $table->text('guardian_address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('esbtp_students');
    }
}
