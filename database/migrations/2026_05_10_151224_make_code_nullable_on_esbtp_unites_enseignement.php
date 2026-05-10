<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX_NAME = 'esbtp_unites_enseignement_code_unique';

    /**
     * Make `code` nullable on `esbtp_unites_enseignement`.
     *
     * UEMOA LMD allows "virtual UEs" — pedagogical groupings (e.g. "UE de Méthodologie")
     * that list ECUEs directly without a formal UE code. MySQL 8 allows multiple NULL
     * values in UNIQUE indexes, so we drop and recreate the unique constraint to
     * preserve uniqueness for non-NULL codes.
     */
    public function up(): void
    {
        $hasIndex = $this->indexExists(self::INDEX_NAME);

        Schema::table('esbtp_unites_enseignement', function (Blueprint $table) use ($hasIndex) {
            if ($hasIndex) {
                $table->dropUnique(self::INDEX_NAME);
            }
            $table->string('code')->nullable()->change();
            $table->unique('code', self::INDEX_NAME);
        });
    }

    public function down(): void
    {
        // Backfill any NULL code before re-applying NOT NULL — virtual UEs would
        // otherwise crash the rollback with a 1138 SQLSTATE error.
        DB::table('esbtp_unites_enseignement')
            ->whereNull('code')
            ->update(['code' => DB::raw("CONCAT('AUTO-', id)")]);

        $hasIndex = $this->indexExists(self::INDEX_NAME);

        Schema::table('esbtp_unites_enseignement', function (Blueprint $table) use ($hasIndex) {
            if ($hasIndex) {
                $table->dropUnique(self::INDEX_NAME);
            }
            $table->string('code')->nullable(false)->change();
            $table->unique('code', self::INDEX_NAME);
        });
    }

    private function indexExists(string $name): bool
    {
        $rows = DB::select(
            'SHOW INDEX FROM esbtp_unites_enseignement WHERE Key_name = ?',
            [$name]
        );
        return count($rows) > 0;
    }
};
