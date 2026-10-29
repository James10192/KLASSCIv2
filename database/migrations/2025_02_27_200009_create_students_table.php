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
        if (!Schema::hasTable('students')) {
            Schema::create('students', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('student_id')->unique()->comment('Numéro étudiant');
                $table->foreignId('parcours_id')->nullable()->constrained()->nullOnDelete();
                $table->string('promotion')->nullable()->comment('Année d\'entrée');
                $table->string('current_year')->nullable()->comment('L1, L2, L3, M1, M2, etc.');
                $table->string('status')->default('active')->comment('Actif, en congé, diplômé, etc.');
                $table->date('registration_date')->nullable();
                $table->date('expected_graduation_date')->nullable();
                $table->date('actual_graduation_date')->nullable();
                $table->string('scholarship_status')->nullable();
                $table->json('scholarship_details')->nullable();
                $table->json('special_needs')->nullable();
                $table->boolean('international_student')->default(false);
                $table->string('country_of_origin')->nullable();
                $table->string('visa_status')->nullable();
                $table->date('visa_expiry_date')->nullable();
                $table->string('emergency_contact_name')->nullable();
                $table->string('emergency_contact_relationship')->nullable();
                $table->string('emergency_contact_phone')->nullable();
                $table->string('previous_institution')->nullable();
                $table->string('previous_qualification')->nullable();
                $table->float('admission_score')->nullable();
                $table->json('notes')->nullable();
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
        Schema::dropIfExists('students');
    }
};
