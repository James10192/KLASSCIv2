<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsAbsentToEsbtpNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('esbtp_notes') && !Schema::hasColumn('esbtp_notes', 'is_absent')) {
            Schema::table('esbtp_notes', function (Blueprint $table) {
                $table->boolean('is_absent')->default(false)->after('note');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('esbtp_notes') && Schema::hasColumn('esbtp_notes', 'is_absent')) {
            Schema::table('esbtp_notes', function (Blueprint $table) {
                $table->dropColumn('is_absent');
            });
        }
    }
}
