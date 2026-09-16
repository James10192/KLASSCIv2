<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('esbtp_seance_cours', function (Blueprint $table) {
            $table->unsignedBigInteger('salle_id')->nullable()->after('salle');
            $table->foreign('salle_id')->references('id')->on('classrooms')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('esbtp_seance_cours', function (Blueprint $table) {
            $table->dropForeign(['salle_id']);
            $table->dropColumn('salle_id');
        });
    }
};
