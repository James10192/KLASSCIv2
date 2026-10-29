<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esbtp_cash_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cashier_user_id');
            $table->date('business_date');
            $table->string('status', 20)->default('open');
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->decimal('counted_amount', 15, 2)->nullable();
            $table->decimal('expected_amount', 15, 2)->nullable();
            $table->decimal('variance', 15, 2)->nullable();
            $table->timestamp('regularized_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['cashier_user_id', 'business_date'], 'uq_cash_session_cashier_date');
            $table->index(['cashier_user_id', 'status']);
            $table->index('business_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esbtp_cash_sessions');
    }
};
