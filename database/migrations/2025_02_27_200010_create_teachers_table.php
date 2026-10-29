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
        if (!Schema::hasTable('teachers')) {
            Schema::create('teachers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('employee_id')->unique()->comment('Numéro d\'employé');
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('laboratory_id')->nullable()->constrained()->nullOnDelete();
                $table->json('specialties')->nullable();
                $table->string('grade')->nullable()->comment('Professeur, Maître de conférences, etc.');
                $table->string('status')->nullable()->comment('PRAG, MCF, PR, vacataire, ATER, etc.');
                $table->integer('teaching_hours_due')->default(0);
                $table->integer('teaching_hours_done')->default(0);
                $table->string('office_location')->nullable();
                $table->json('office_hours')->nullable();
                $table->text('bio')->nullable();
                $table->json('research_interests')->nullable();
                $table->json('publications')->nullable();
                $table->string('website')->nullable();
                $table->json('availability')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('teachers');
    }
};
