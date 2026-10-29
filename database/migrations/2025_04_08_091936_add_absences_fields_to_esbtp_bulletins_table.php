<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAbsencesFieldsToEsbtpBulletinsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('esbtp_bulletins', function (Blueprint $table) {
            // Ajout des champs pour les absences
            if (!Schema::hasColumn('esbtp_bulletins', 'absences_justifiees')) {
                $table->float('absences_justifiees')->default(0)->comment('Nombre d\'heures d\'absences justifiées');
            }
            if (!Schema::hasColumn('esbtp_bulletins', 'absences_non_justifiees')) {
                $table->float('absences_non_justifiees')->default(0)->comment('Nombre d\'heures d\'absences non justifiées');
            }
            if (!Schema::hasColumn('esbtp_bulletins', 'total_absences')) {
                $table->float('total_absences')->default(0)->comment('Total des heures d\'absences');
            }
            if (!Schema::hasColumn('esbtp_bulletins', 'note_assiduite')) {
                $table->float('note_assiduite')->nullable()->comment('Note d\'assiduité calculée sur les absences');
            }
            if (!Schema::hasColumn('esbtp_bulletins', 'details_absences')) {
                $table->json('details_absences')->nullable()->comment('Détails des absences au format JSON');
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
            // Suppression des champs d'absences
            $columns = [
                'absences_justifiees',
                'absences_non_justifiees',
                'total_absences',
                'note_assiduite',
                'details_absences'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('esbtp_bulletins', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
