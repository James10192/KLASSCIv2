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
            // Supprimer la contrainte de clé étrangère d'abord
            $table->dropForeign(['daily_code_id']);

            // Modifier la colonne pour la rendre nullable
            $table->unsignedBigInteger('daily_code_id')->nullable()->change();

            // Recréer la contrainte de clé étrangère avec nullable
            $table->foreign('daily_code_id')
                ->references('id')
                ->on('esbtp_daily_codes')
                ->onDelete('set null');
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
            // Supprimer la contrainte nullable
            $table->dropForeign(['daily_code_id']);

            // Remettre NOT NULL
            $table->unsignedBigInteger('daily_code_id')->nullable(false)->change();

            // Recréer la contrainte avec cascade
            $table->foreign('daily_code_id')
                ->references('id')
                ->on('esbtp_daily_codes')
                ->onDelete('cascade');
        });
    }
};
