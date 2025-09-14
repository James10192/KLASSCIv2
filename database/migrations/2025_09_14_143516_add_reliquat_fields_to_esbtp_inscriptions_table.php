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
            // Champ pour stocker le montant du reliquat reporté depuis l'année précédente
            $table->decimal('reliquat_annee_precedente', 10, 2)->default(0)->after('observations');

            // ID de l'inscription source du reliquat (pour traçabilité)
            $table->foreignId('reliquat_source_inscription_id')->nullable()->after('reliquat_annee_precedente')
                  ->constrained('esbtp_inscriptions')->onDelete('set null');

            // Champ pour indiquer si cette inscription a été créée avec un reliquat
            $table->boolean('has_reliquat')->default(false)->after('reliquat_source_inscription_id');

            // Notes sur le reliquat (optionnel)
            $table->text('reliquat_notes')->nullable()->after('has_reliquat');
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
            $table->dropForeign(['reliquat_source_inscription_id']);
            $table->dropColumn([
                'reliquat_annee_precedente',
                'reliquat_source_inscription_id',
                'has_reliquat',
                'reliquat_notes'
            ]);
        });
    }
};
