<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEvaluationIdToEsbtpNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('esbtp_notes') && !Schema::hasColumn('esbtp_notes', 'evaluation_id')) {
            Schema::table('esbtp_notes', function (Blueprint $table) {
                $table->foreignId('evaluation_id')->after('id')->nullable()->constrained('esbtp_evaluations')->onDelete('cascade');
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
        if (Schema::hasTable('esbtp_notes') && Schema::hasColumn('esbtp_notes', 'evaluation_id')) {
            Schema::table('esbtp_notes', function (Blueprint $table) {
                $table->dropForeign(['evaluation_id']);
                $table->dropColumn('evaluation_id');
            });
        }
    }
}
