<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToTeacherAttendanceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('esbtp_teacher_attendance', function (Blueprint $table) {
            $table->foreign('teacher_id')
                  ->references('id')
                  ->on('esbtp_teachers')
                  ->onDelete('cascade');

            $table->foreign('emploi_du_temps_id')
                  ->references('id')
                  ->on('esbtp_emplois_du_temps')
                  ->onDelete('cascade');

            $table->foreign('daily_code_id')
                  ->references('id')
                  ->on('esbtp_daily_codes')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('esbtp_teacher_attendance', function (Blueprint $table) {
            $table->dropForeign(['teacher_id']);
            $table->dropForeign(['emploi_du_temps_id']);
            $table->dropForeign(['daily_code_id']);
        });
    }
}
