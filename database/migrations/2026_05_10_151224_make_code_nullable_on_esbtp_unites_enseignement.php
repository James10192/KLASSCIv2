<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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
        Schema::table('esbtp_unites_enseignement', function (Blueprint $table) {
            $table->dropUnique('esbtp_unites_enseignement_code_unique');
        });

        Schema::table('esbtp_unites_enseignement', function (Blueprint $table) {
            $table->string('code')->nullable()->change();
            $table->unique('code', 'esbtp_unites_enseignement_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('esbtp_unites_enseignement', function (Blueprint $table) {
            $table->dropUnique('esbtp_unites_enseignement_code_unique');
        });

        Schema::table('esbtp_unites_enseignement', function (Blueprint $table) {
            $table->string('code')->nullable(false)->change();
            $table->unique('code', 'esbtp_unites_enseignement_code_unique');
        });
    }
};
