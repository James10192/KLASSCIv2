<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('fee_category_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_category_id')->constrained('fee_categories')->onDelete('cascade');
            $table->foreignId('filiere_id')->nullable()->constrained('esbtp_filieres')->onDelete('set null');
            $table->foreignId('niveau_id')->nullable()->constrained('esbtp_niveau_etudes')->onDelete('set null');
            $table->foreignId('annee_universitaire_id')->nullable()->constrained('esbtp_annee_universitaires')->onDelete('set null');
            $table->decimal('amount', 10, 2);
            $table->enum('payment_schedule', ['one_time', 'monthly', 'termly', 'yearly'])->default('one_time');
            $table->boolean('installments_allowed')->default(false);
            $table->decimal('min_installment_amount', 10, 2)->nullable();
            $table->decimal('late_fee', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('fee_category_rules');
    }
};
