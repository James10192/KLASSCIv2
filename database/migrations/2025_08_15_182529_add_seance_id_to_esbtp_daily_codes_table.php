<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSeanceIdToEsbtpDailyCodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('esbtp_daily_codes', function (Blueprint $table) {
            $table->unsignedBigInteger('seance_id')->nullable()->after('type');
            $table->foreign('seance_id')->references('id')->on('esbtp_seance_cours')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('esbtp_daily_codes', function (Blueprint $table) {
            $table->dropForeign(['seance_id']);
            $table->dropColumn('seance_id');
        });
    }
}
