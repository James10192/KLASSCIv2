<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReinscriptionStatusToEsbtpInscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('esbtp_inscriptions', function (Blueprint $table) {
            $table->enum('reinscription_status', ['pending', 'validated', 'abandoned'])
                  ->nullable()
                  ->comment('Statut de la réinscription: pending=en attente, validated=validée, abandoned=abandonné');
            $table->timestamp('reinscription_validated_at')->nullable();
            $table->unsignedBigInteger('reinscription_validated_by')->nullable();
            $table->text('reinscription_observations')->nullable();
            
            $table->foreign('reinscription_validated_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('esbtp_inscriptions', function (Blueprint $table) {
            $table->dropForeign(['reinscription_validated_by']);
            $table->dropColumn([
                'reinscription_status', 
                'reinscription_validated_at', 
                'reinscription_validated_by', 
                'reinscription_observations'
            ]);
        });
    }
}
