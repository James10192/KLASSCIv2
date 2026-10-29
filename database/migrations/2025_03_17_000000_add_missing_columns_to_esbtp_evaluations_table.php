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
        if (Schema::hasTable('esbtp_evaluations')) {
            Schema::table('esbtp_evaluations', function (Blueprint $table) {
                // Vérifier si chaque colonne existe avant de l'ajouter
                if (!Schema::hasColumn('esbtp_evaluations', 'titre')) {
                    $table->string('titre')->after('id');
                }
                if (!Schema::hasColumn('esbtp_evaluations', 'description')) {
                    $table->text('description')->nullable()->after('titre');
                }
                if (!Schema::hasColumn('esbtp_evaluations', 'duree_minutes')) {
                    $table->integer('duree_minutes')->nullable()->after('bareme');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('esbtp_evaluations')) {
            Schema::table('esbtp_evaluations', function (Blueprint $table) {
                // Vérifier si chaque colonne existe avant de la supprimer
                $columns = [];
                if (Schema::hasColumn('esbtp_evaluations', 'titre')) {
                    $columns[] = 'titre';
                }
                if (Schema::hasColumn('esbtp_evaluations', 'description')) {
                    $columns[] = 'description';
                }
                if (Schema::hasColumn('esbtp_evaluations', 'duree_minutes')) {
                    $columns[] = 'duree_minutes';
                }

                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
