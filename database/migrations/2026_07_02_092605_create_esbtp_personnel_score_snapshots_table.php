<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        if (Schema::hasTable('esbtp_personnel_score_snapshots')) {
            $this->ensureIndexes();
            return;
        }

        Schema::create('esbtp_personnel_score_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('esbtp_teachers')->nullOnDelete();
            $table->string('role_name', 80)->nullable();
            $table->string('period_type', 20)->default('month');
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedTinyInteger('total_score')->default(0);
            $table->string('level', 30)->default('insufficient_data');
            $table->unsignedSmallInteger('applicable_dimensions_count')->default(0);
            $table->unsignedSmallInteger('excluded_dimensions_count')->default(0);
            $table->json('permissions')->nullable();
            $table->json('metrics')->nullable();
            $table->json('breakdown')->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['user_id', 'role_name', 'period_type', 'period_start', 'period_end'],
                'epss_period_unique'
            );
            $table->index(['role_name', 'period_type', 'period_start'], 'epss_role_period_idx');
            $table->index(['level', 'total_score'], 'epss_level_score_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_personnel_score_snapshots');
    }

    private function ensureIndexes(): void
    {
        Schema::table('esbtp_personnel_score_snapshots', function (Blueprint $table) {
            if (! $this->indexExists('esbtp_personnel_score_snapshots', 'epss_role_period_idx')) {
                $table->index(['role_name', 'period_type', 'period_start'], 'epss_role_period_idx');
            }

            if (! $this->indexExists('esbtp_personnel_score_snapshots', 'epss_level_score_idx')) {
                $table->index(['level', 'total_score'], 'epss_level_score_idx');
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        return collect(DB::select("SHOW INDEX FROM {$table}"))
            ->contains(fn ($row) => ($row->Key_name ?? null) === $index);
    }
};
