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
        Schema::create('esbtp_inscription_echeancier_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inscription_id');
            $table->unsignedSmallInteger('snapshot_version')->default(1);
            $table->json('payload');
            $table->timestamp('generated_at')->nullable();
            $table->decimal('computed_overdue_amount', 12, 2)->default(0);
            $table->unsignedInteger('computed_overdue_days')->default(0);
            $table->timestamp('last_recomputed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique('inscription_id', 'uq_inscription_echeancier_snapshot');
            $table->index(['computed_overdue_amount', 'computed_overdue_days'], 'idx_echeancier_snapshot_overdue');
            $table->index('last_recomputed_at', 'idx_echeancier_snapshot_last_recompute');

            $table->foreign('inscription_id', 'fk_echeancier_snapshot_inscription')
                ->references('id')->on('esbtp_inscriptions')
                ->onDelete('cascade');
            $table->foreign('created_by', 'fk_echeancier_snapshot_created_by')
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
        Schema::dropIfExists('esbtp_inscription_echeancier_snapshots');
    }
};
