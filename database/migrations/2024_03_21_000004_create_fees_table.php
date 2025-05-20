<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('esbtp_classes')->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained('esbtp_annee_universitaires')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->enum('payment_schedule', ['one_time', 'monthly', 'termly', 'yearly']);
            $table->boolean('installments_allowed')->default(false);
            $table->decimal('min_installment_amount', 10, 2)->nullable();
            $table->decimal('late_fee', 10, 2)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('fees');
    }
};
