<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('esbtp_attendances', function (Blueprint $table) {
            // Supprimer l'ancienne clé étrangère
            $table->dropForeign(['cours_id']);

            // Renommer la colonne
            $table->renameColumn('cours_id', 'seance_cours_id');

            // Ajouter la nouvelle clé étrangère
            $table->foreign('seance_cours_id')
                  ->references('id')
                  ->on('esbtp_seance_cours')
                  ->onDelete('cascade');

            // Renommer d'autres colonnes si nécessaire
            if (Schema::hasColumn('esbtp_attendances', 'status')) {
                $table->renameColumn('status', 'statut');
            }

            if (Schema::hasColumn('esbtp_attendances', 'notes')) {
                $table->renameColumn('notes', 'commentaire');
            }
        });
    }

    public function down()
    {
        Schema::table('esbtp_attendances', function (Blueprint $table) {
            // Supprimer la nouvelle clé étrangère
            $table->dropForeign(['seance_cours_id']);

            // Renommer les colonnes à leur état d'origine
            $table->renameColumn('seance_cours_id', 'cours_id');

            if (Schema::hasColumn('esbtp_attendances', 'statut')) {
                $table->renameColumn('statut', 'status');
            }

            if (Schema::hasColumn('esbtp_attendances', 'commentaire')) {
                $table->renameColumn('commentaire', 'notes');
            }

            // Rétablir l'ancienne clé étrangère
            $table->foreign('cours_id')
                  ->references('id')
                  ->on('esbtp_cours')
                  ->onDelete('cascade');
        });
    }
};