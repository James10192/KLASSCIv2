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
        Schema::table('esbtp_teacher_attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('esbtp_teacher_attendances', 'justification')) {
                // Motif d'un émargement accepté après le délai de retard, quand
                // l'école a choisi la conduite « justification » plutôt que
                // l'absence d'office.
                $table->text('justification')->nullable()->after('status');
            }
            if (! Schema::hasColumn('esbtp_teacher_attendances', 'minutes_retard')) {
                $table->unsignedSmallInteger('minutes_retard')->nullable()->after('status');
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
        Schema::table('esbtp_teacher_attendances', function (Blueprint $table) {
            foreach (['justification', 'minutes_retard'] as $colonne) {
                if (Schema::hasColumn('esbtp_teacher_attendances', $colonne)) {
                    $table->dropColumn($colonne);
                }
            }
        });
    }
};
