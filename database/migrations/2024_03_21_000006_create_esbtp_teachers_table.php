<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateESBTPTeachersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('esbtp_teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('matricule')->unique();
            $table->string('title')->nullable();
            $table->string('specialization')->nullable();
            $table->string('status');
            $table->decimal('teaching_hours_due', 8, 2)->default(0);
            $table->text('bio')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();
            $table->text('research_interests')->nullable();
            $table->string('website')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('department_id')->nullable()->constrained('esbtp_departments')->onDelete('set null');
            $table->foreignId('laboratory_id')->nullable()->constrained('esbtp_laboratories')->onDelete('set null');
            $table->string('grade')->nullable();
            $table->string('office_location')->nullable();
            $table->string('employee_id')->nullable()->unique();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('matricule');
            $table->index('status');
            $table->index('is_active');
            $table->index('employee_id');
            $table->index('department_id');
            $table->index('laboratory_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_teachers');
    }
}
