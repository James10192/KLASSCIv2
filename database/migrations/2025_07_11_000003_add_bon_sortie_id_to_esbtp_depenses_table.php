<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBonSortieIdToEsbtpDepensesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('esbtp_depenses', function (Blueprint $table) {
            $table->foreignId('bon_sortie_id')->nullable()->constrained('esbtp_bons_sortie')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('esbtp_depenses', function (Blueprint $table) {
            $table->dropForeign(['bon_sortie_id']);
            $table->dropColumn('bon_sortie_id');
        });
    }
} 