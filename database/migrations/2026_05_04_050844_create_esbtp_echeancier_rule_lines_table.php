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
        Schema::create('esbtp_echeancier_rule_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rule_id');
            $table->string('label', 120);
            $table->unsignedInteger('sort_order')->default(1);
            $table->string('amount_mode', 20)->comment('percent|fixed');
            $table->decimal('amount_value', 12, 2)->default(0);
            $table->string('due_mode', 20)->comment('days_after_inscription|fixed_mm_dd');
            $table->string('due_value', 20)->comment('days count or MM-DD');
            $table->unsignedInteger('grace_days')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['rule_id', 'sort_order'], 'idx_echeancier_lines_rule_order');
            $table->index(['rule_id', 'is_active'], 'idx_echeancier_lines_rule_active');

            $table->foreign('rule_id', 'fk_echeancier_lines_rule')
                ->references('id')->on('esbtp_echeancier_rules')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_echeancier_rule_lines');
    }
};
