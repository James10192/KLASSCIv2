<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProfesseursToEsbtpBulletinsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('esbtp_bulletins', function (Blueprint $table) {
            if (!Schema::hasColumn('esbtp_bulletins', 'professeurs')) {
                $table->json('professeurs')->nullable()->after('config_matieres');
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
        Schema::table('esbtp_bulletins', function (Blueprint $table) {
            if (Schema::hasColumn('esbtp_bulletins', 'professeurs')) {
                $table->dropColumn('professeurs');
            }
        });
    }
}
