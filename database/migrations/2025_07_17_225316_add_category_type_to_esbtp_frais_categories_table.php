<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddCategoryTypeToEsbtpFraisCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('esbtp_frais_categories', function (Blueprint $table) {
            // Ajouter le champ category_type avec une valeur par défaut
            $table->enum('category_type', ['academic', 'service', 'administrative'])
                  ->default('academic')
                  ->after('is_mandatory')
                  ->comment('Type de catégorie: academic (inscription/scolarité), service (cantine/transport), administrative (documents/examens)');
        });

        // Mise à jour des données existantes basée sur les codes
        DB::statement("
            UPDATE esbtp_frais_categories 
            SET category_type = CASE 
                WHEN code IN ('INSCRIPTION', 'SCOLARITE') THEN 'academic'
                WHEN code IN ('CANTINE', 'TRANSPORT') THEN 'service'
                WHEN code IN ('DOCUMENTATION', 'EXAMEN') THEN 'administrative'
                WHEN is_mandatory = 1 THEN 'academic'
                ELSE 'service'
            END
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('esbtp_frais_categories', function (Blueprint $table) {
            $table->dropColumn('category_type');
        });
    }
}
