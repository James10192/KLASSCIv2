<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEnseignantIdToEsbtpEvaluationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('esbtp_evaluations', function (Blueprint $table) {
            $table->unsignedBigInteger('enseignant_id')->nullable()->after('created_by');
            $table->string('enseignant_externe_nom')->nullable()->after('enseignant_id');
            $table->string('token_saisie_externe')->nullable()->after('enseignant_externe_nom');
            $table->datetime('token_expire_at')->nullable()->after('token_saisie_externe');
            
            $table->foreign('enseignant_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['token_saisie_externe']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('esbtp_evaluations', function (Blueprint $table) {
            $table->dropForeign(['enseignant_id']);
            $table->dropIndex(['token_saisie_externe']);
            $table->dropColumn(['enseignant_id', 'enseignant_externe_nom', 'token_saisie_externe', 'token_expire_at']);
        });
    }
}
