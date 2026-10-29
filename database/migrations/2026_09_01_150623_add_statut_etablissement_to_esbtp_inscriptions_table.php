<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('esbtp_inscriptions', function (Blueprint $table) {
            $table->string('statut_etablissement', 20)->nullable()->after('etablissement_origine');
        });
    }

    public function down()
    {
        Schema::table('esbtp_inscriptions', function (Blueprint $table) {
            $table->dropColumn('statut_etablissement');
        });
    }
};
