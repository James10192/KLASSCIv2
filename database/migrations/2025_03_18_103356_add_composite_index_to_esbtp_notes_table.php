<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCompositeIndexToEsbtpNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('esbtp_notes')) {
            Schema::table('esbtp_notes', function (Blueprint $table) {
                if (!$this->hasIndex('esbtp_notes', 'idx_notes_etudiant_semestre_classe')) {
                    $table->index(['etudiant_id', 'semestre', 'classe_id'], 'idx_notes_etudiant_semestre_classe');
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
        if (Schema::hasTable('esbtp_notes')) {
            Schema::table('esbtp_notes', function (Blueprint $table) {
                if ($this->hasIndex('esbtp_notes', 'idx_notes_etudiant_semestre_classe')) {
                    $table->dropIndex('idx_notes_etudiant_semestre_classe');
                }
            });
        }
    }

    /**
     * Vérifie si un index existe dans une table
     *
     * @param string $table
     * @param string $index
     * @return bool
     */
    private function hasIndex($table, $index)
    {
        // Depuis Laravel 11, le schema natif remplace Doctrine pour les index.
        return Schema::hasIndex($table, $index);
    }
}
