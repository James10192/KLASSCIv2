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
            // Étape 1: Créer des index séparés sur teacher_id et course_id si nécessaire
            // Cela garantit que les FK peuvent toujours fonctionner après suppression de la contrainte unique
            if (!$this->indexExists('esbtp_teacher_attendances', 'esbtp_teacher_attendances_teacher_id_index')) {
                $table->index('teacher_id', 'esbtp_teacher_attendances_teacher_id_index');
            }
            if (!$this->indexExists('esbtp_teacher_attendances', 'esbtp_teacher_attendances_course_id_index')) {
                $table->index('course_id', 'esbtp_teacher_attendances_course_id_index');
            }
        });

        // Étape 2: Supprimer l'ancienne contrainte unique
        Schema::table('esbtp_teacher_attendances', function (Blueprint $table) {
            $table->dropUnique('uniq_teacher_course_date');
        });

        // Étape 3: Ajouter la nouvelle contrainte unique incluant le type
        Schema::table('esbtp_teacher_attendances', function (Blueprint $table) {
            $table->unique(['teacher_id', 'course_id', 'date', 'type'], 'uniq_teacher_course_date_type');
        });
    }

    /**
     * Vérifier si un index existe déjà
     */
    private function indexExists($table, $indexName)
    {
        $indexes = \DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('esbtp_teacher_attendances', function (Blueprint $table) {
            // Restaurer l'ancienne contrainte unique
            $table->dropUnique('uniq_teacher_course_date_type');
            $table->unique(['teacher_id', 'course_id', 'date'], 'uniq_teacher_course_date');
        });
    }
};
