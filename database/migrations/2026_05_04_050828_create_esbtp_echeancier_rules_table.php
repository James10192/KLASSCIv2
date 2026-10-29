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
        Schema::create('esbtp_echeancier_rules', function (Blueprint $table) {
            $table->id();
            $table->string('scope_type', 40)->comment('configuration|option_assignment');
            $table->unsignedBigInteger('scope_id')->comment('ID of scope record');
            $table->string('affectation_status', 30)->default('all')->comment('all|affecté|réaffecté|non_affecté');
            $table->unsignedInteger('priority')->default(100);
            $table->boolean('is_active')->default(true);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['scope_type', 'scope_id'], 'idx_echeancier_scope');
            $table->index(['is_active', 'effective_from', 'effective_to'], 'idx_echeancier_active_window');
            $table->index(['scope_type', 'scope_id', 'affectation_status', 'priority'], 'idx_echeancier_scope_status');
            $table->unique(['scope_type', 'scope_id', 'affectation_status'], 'uq_echeancier_scope_status');

            $table->foreign('created_by', 'fk_echeancier_rules_created_by')
                ->references('id')->on('users')
                ->onDelete('set null');
            $table->foreign('updated_by', 'fk_echeancier_rules_updated_by')
                ->references('id')->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_echeancier_rules');
    }
};
