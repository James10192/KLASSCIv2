<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddConfigColumnToEsbtpConfigMatieresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('esbtp_config_matieres', function (Blueprint $table) {
            if (!Schema::hasColumn('esbtp_config_matieres', 'config')) {
                $table->json('config')->nullable()->after('periode');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('esbtp_config_matieres', function (Blueprint $table) {
            if (Schema::hasColumn('esbtp_config_matieres', 'config')) {
                $table->dropColumn('config');
            }
        });
    }
}
