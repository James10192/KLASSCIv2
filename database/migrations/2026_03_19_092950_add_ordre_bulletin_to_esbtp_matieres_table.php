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
        Schema::table('esbtp_matieres', function (Blueprint $table) {
            if (!Schema::hasColumn('esbtp_matieres', 'ordre_bulletin')) {
                $table->unsignedSmallInteger('ordre_bulletin')->default(0)->after('coefficient_ecue');
            }
        });
    }

    public function down()
    {
        Schema::table('esbtp_matieres', function (Blueprint $table) {
            $table->dropColumn('ordre_bulletin');
        });
    }
};
