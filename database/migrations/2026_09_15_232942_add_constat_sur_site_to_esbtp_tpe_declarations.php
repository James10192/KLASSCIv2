<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('esbtp_tpe_declarations', function (Blueprint $table) {
            $table->unsignedBigInteger('seance_id')->nullable()->after('matiere_id');
            $table->string('lieu', 100)->nullable()->after('heures');
            $table->time('heure_debut')->nullable()->after('lieu');
            $table->time('heure_fin')->nullable()->after('heure_debut');
            $table->foreign('seance_id')->references('id')->on('esbtp_seance_cours')->nullOnDelete();
            $table->unique(['etudiant_id', 'seance_id'], 'tpe_decl_seance_unique');
        });
    }

    public function down()
    {
        Schema::table('esbtp_tpe_declarations', function (Blueprint $table) {
            $table->dropUnique('tpe_decl_seance_unique');
            $table->dropForeign(['seance_id']);
            $table->dropColumn(['seance_id', 'lieu', 'heure_debut', 'heure_fin']);
        });
    }
};
