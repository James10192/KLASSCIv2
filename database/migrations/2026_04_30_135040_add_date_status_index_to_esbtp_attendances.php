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
        Schema::table('esbtp_attendances', function (Blueprint $table) {
            // Composite index utilisé par le widget « taux de présence du jour »
            // (whereDate('date') + filter sur status). Sans cet index, full table scan
            // sur ~700k rows en fin d'année (700 étudiants × 5 séances × 200 jours).
            $table->index(['date', 'status'], 'idx_esbtp_attendances_date_status');
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
            $table->dropIndex('idx_esbtp_attendances_date_status');
        });
    }
};
