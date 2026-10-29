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
            $table->unsignedBigInteger('validateur_id')->nullable()->after('date_validation');
            $table->foreign('validateur_id')->references('id')->on('users')->onDelete('set null');
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
            $table->dropForeign(['validateur_id']);
            $table->dropColumn('validateur_id');
        });
    }
};
