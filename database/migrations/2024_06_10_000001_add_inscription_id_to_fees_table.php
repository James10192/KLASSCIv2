<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('fees', function (Blueprint $table) {
            $table->unsignedBigInteger('inscription_id')->nullable()->after('id');
            $table->foreign('inscription_id')->references('id')->on('esbtp_inscriptions')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('fees', function (Blueprint $table) {
            $table->dropForeign(['inscription_id']);
            $table->dropColumn('inscription_id');
        });
    }
};
