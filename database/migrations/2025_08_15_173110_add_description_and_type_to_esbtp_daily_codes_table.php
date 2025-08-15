<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDescriptionAndTypeToEsbtpDailyCodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('esbtp_daily_codes', function (Blueprint $table) {
            $table->string('description')->nullable()->after('created_by');
            $table->enum('type', ['session', 'journee', 'personnalise'])->default('session')->after('description');
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
            $table->dropColumn(['description', 'type']);
        });
    }
}
