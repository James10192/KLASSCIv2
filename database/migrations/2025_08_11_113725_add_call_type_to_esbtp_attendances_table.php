<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCallTypeToEsbtpAttendancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('esbtp_attendances', function (Blueprint $table) {
            $table->enum('call_type', ['start', 'end'])->default('start')->after('statut');
            $table->index(['seance_cours_id', 'call_type'], 'idx_seance_call_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('esbtp_attendances', function (Blueprint $table) {
            $table->dropIndex('idx_seance_call_type');
            $table->dropColumn('call_type');
        });
    }
}
