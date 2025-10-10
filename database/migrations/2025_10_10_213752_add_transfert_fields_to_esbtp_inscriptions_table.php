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
        Schema::table('esbtp_inscriptions', function (Blueprint $table) {
            // Champ booléen pour indiquer si l'étudiant vient d'un transfert
            $table->boolean('est_transfert')->default(false)->after('type_inscription');

            // Champ optionnel pour stocker le nom de l'établissement d'origine
            $table->string('etablissement_origine', 255)->nullable()->after('est_transfert');
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
            $table->dropColumn(['est_transfert', 'etablissement_origine']);
        });
    }
};
