<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToEsbtpAttendancesTable extends Migration
{
    public function up()
    {
        Schema::table('esbtp_attendances', function (Blueprint $table) {
            $table->enum('status', ['present', 'absent', 'retard'])->default('present')->after('etudiant_id');
        });
    }

    public function down()
    {
        Schema::table('esbtp_attendances', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}
