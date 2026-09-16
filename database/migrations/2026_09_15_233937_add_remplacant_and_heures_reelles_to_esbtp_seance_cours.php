<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('esbtp_seance_cours', function (Blueprint $table) {
            $table->unsignedBigInteger('remplacant_id')->nullable()->after('teacher_id');
            $table->time('heure_reelle_debut')->nullable()->after('heure_fin');
            $table->time('heure_reelle_fin')->nullable()->after('heure_reelle_debut');
            $table->foreign('remplacant_id')->references('id')->on('esbtp_teachers')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('esbtp_seance_cours', function (Blueprint $table) {
            $table->dropForeign(['remplacant_id']);
            $table->dropColumn(['remplacant_id', 'heure_reelle_debut', 'heure_reelle_fin']);
        });
    }
};
