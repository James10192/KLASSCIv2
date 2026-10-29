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
            $table->foreignId('inscription_origine_id')->nullable()->after('classe_alternative_id')
                ->constrained('esbtp_inscriptions')->nullOnDelete();
            $table->string('type_changement')->nullable()->after('inscription_origine_id');
        });

        // Modifier la contrainte unique pour permettre 2 inscriptions/an dans 2 classes différentes
        // Ancienne : (etudiant_id, annee_universitaire_id, status)
        // Nouvelle : (etudiant_id, annee_universitaire_id, classe_id)

        // 1. Créer le nouvel index AVANT de drop l'ancien
        //    (MySQL a besoin d'un index sur etudiant_id pour la FK etudiant_id_foreign)
        Schema::table('esbtp_inscriptions', function (Blueprint $table) {
            $table->unique(['etudiant_id', 'annee_universitaire_id', 'classe_id'], 'inscriptions_etudiant_annee_classe_unique');
        });

        // 2. Drop l'ancienne contrainte (MySQL peut maintenant utiliser le nouvel index pour la FK)
        Schema::table('esbtp_inscriptions', function (Blueprint $table) {
            // Ne plus utiliser Doctrine (API supprimee des connexions Laravel 12).
            foreach (Schema::getIndexes('esbtp_inscriptions') as $index) {
                $columns = $index['columns'] ?? [];
                if (! ($index['unique'] ?? false)) {
                    continue;
                }
                if (count($columns) === 3
                    && in_array('etudiant_id', $columns, true)
                    && in_array('annee_universitaire_id', $columns, true)
                    && in_array('status', $columns, true)) {
                    $table->dropUnique($index['name']);
                    break;
                }
            }
        });
    }

    public function down()
    {
        Schema::table('esbtp_inscriptions', function (Blueprint $table) {
            $table->dropUnique('inscriptions_etudiant_annee_classe_unique');
        });

        Schema::table('esbtp_inscriptions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inscription_origine_id');
            $table->dropColumn('type_changement');
        });
    }
};
