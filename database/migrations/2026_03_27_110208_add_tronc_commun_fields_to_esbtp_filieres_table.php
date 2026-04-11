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
        Schema::table('esbtp_filieres', function (Blueprint $table) {
            $table->boolean('is_tronc_commun')->default(false)->after('is_active');
            $table->unsignedTinyInteger('semestres_tronc_commun')->default(1)->after('is_tronc_commun');
        });
    }

    public function down()
    {
        Schema::table('esbtp_filieres', function (Blueprint $table) {
            $table->dropColumn(['is_tronc_commun', 'semestres_tronc_commun']);
        });
    }
};
