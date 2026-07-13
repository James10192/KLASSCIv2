<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('esbtp_personnel_score_snapshots')) {
            return;
        }

        $this->addColumnIfMissing('coverage', fn (Blueprint $table) => $table->decimal('coverage', 5, 4)->nullable());
        $this->addColumnIfMissing('confidence', fn (Blueprint $table) => $table->decimal('confidence', 5, 4)->nullable());
        $this->addColumnIfMissing('engine_version', fn (Blueprint $table) => $table->string('engine_version', 80)->nullable());
        $this->addColumnIfMissing('evidence_hash', fn (Blueprint $table) => $table->char('evidence_hash', 64)->nullable());
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('esbtp_personnel_score_snapshots')) {
            return;
        }

        foreach (['evidence_hash', 'engine_version', 'confidence', 'coverage'] as $column) {
            if (Schema::hasColumn('esbtp_personnel_score_snapshots', $column)) {
                Schema::table('esbtp_personnel_score_snapshots', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }

    private function addColumnIfMissing(string $column, Closure $definition): void
    {
        if (! Schema::hasColumn('esbtp_personnel_score_snapshots', $column)) {
            Schema::table('esbtp_personnel_score_snapshots', $definition);
        }
    }
};
