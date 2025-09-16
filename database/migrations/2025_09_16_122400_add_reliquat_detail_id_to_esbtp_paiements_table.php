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
        Schema::table('esbtp_paiements', function (Blueprint $table) {
            $table->unsignedBigInteger('reliquat_detail_id')->nullable()->after('relance_id');
            $table->index('reliquat_detail_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('esbtp_paiements', function (Blueprint $table) {
            $table->dropIndex(['reliquat_detail_id']);
            $table->dropColumn('reliquat_detail_id');
        });
    }
};
