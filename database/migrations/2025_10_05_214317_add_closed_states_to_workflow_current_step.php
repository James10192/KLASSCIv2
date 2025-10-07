<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Pour MySQL, on doit modifier l'ENUM avec ALTER TABLE
        DB::statement("ALTER TABLE esbtp_session_workflow_status
            MODIFY COLUMN current_step ENUM(
                'attendance',
                'call_start',
                'call_end',
                'report',
                'completed',
                'closed_absent',
                'closed_incomplete'
            ) DEFAULT 'attendance'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Retirer les deux nouveaux états
        DB::statement("ALTER TABLE esbtp_session_workflow_status
            MODIFY COLUMN current_step ENUM(
                'attendance',
                'call_start',
                'call_end',
                'report',
                'completed'
            ) DEFAULT 'attendance'");
    }
};
