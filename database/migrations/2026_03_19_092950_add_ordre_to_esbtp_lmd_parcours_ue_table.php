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
        Schema::table('esbtp_lmd_parcours_ue', function (Blueprint $table) {
            if (!Schema::hasColumn('esbtp_lmd_parcours_ue', 'ordre')) {
                $table->unsignedSmallInteger('ordre')->default(0)->after('is_optional');
            }
        });
    }

    public function down()
    {
        Schema::table('esbtp_lmd_parcours_ue', function (Blueprint $table) {
            $table->dropColumn('ordre');
        });
    }
};
