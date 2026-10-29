<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHeadTeacherToDepartmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('esbtp_departments', function (Blueprint $table) {
            // Ajouter seulement colonne head_teacher_id pour référencer un enseignant
            $table->unsignedBigInteger('head_teacher_id')->nullable()->after('description');
            
            // Clé étrangère vers la table des enseignants
            $table->foreign('head_teacher_id')->references('id')->on('esbtp_teachers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('esbtp_departments', function (Blueprint $table) {
            $table->dropForeign(['head_teacher_id']);
            $table->dropColumn(['head_teacher_id']);
        });
    }
}
