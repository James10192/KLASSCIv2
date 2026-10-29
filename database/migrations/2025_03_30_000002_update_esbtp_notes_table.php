<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('esbtp_notes')) {
            Schema::table('esbtp_notes', function (Blueprint $table) {
                // Ajouter les nouveaux champs
                if (!Schema::hasColumn('esbtp_notes', 'semestre')) {
                    $table->string('semestre')->after('etudiant_id');
                }

                if (!Schema::hasColumn('esbtp_notes', 'annee_universitaire')) {
                    $table->string('annee_universitaire')->after('semestre');
                }

                if (!Schema::hasColumn('esbtp_notes', 'type_evaluation')) {
                    $table->string('type_evaluation')->after('note');
                }

                if (!Schema::hasColumn('esbtp_notes', 'moyenne_matiere')) {
                    $table->decimal('moyenne_matiere', 5, 2)->nullable()->after('type_evaluation');
                }

                if (!Schema::hasColumn('esbtp_notes', 'rang_matiere')) {
                    $table->integer('rang_matiere')->nullable()->after('moyenne_matiere');
                }

                if (!Schema::hasColumn('esbtp_notes', 'appreciation')) {
                    $table->text('appreciation')->nullable()->after('rang_matiere');
                }

                // Modifier les champs existants si la colonne note existe
                if (Schema::hasColumn('esbtp_notes', 'note')) {
                    $table->decimal('note', 5, 2)->change();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('esbtp_notes')) {
            // Préparer la liste des colonnes à supprimer qui existent
            $columns = [];

            if (Schema::hasColumn('esbtp_notes', 'semestre')) {
                $columns[] = 'semestre';
            }

            if (Schema::hasColumn('esbtp_notes', 'annee_universitaire')) {
                $columns[] = 'annee_universitaire';
            }

            if (Schema::hasColumn('esbtp_notes', 'type_evaluation')) {
                $columns[] = 'type_evaluation';
            }

            if (Schema::hasColumn('esbtp_notes', 'moyenne_matiere')) {
                $columns[] = 'moyenne_matiere';
            }

            if (Schema::hasColumn('esbtp_notes', 'rang_matiere')) {
                $columns[] = 'rang_matiere';
            }

            if (Schema::hasColumn('esbtp_notes', 'appreciation')) {
                $columns[] = 'appreciation';
            }

            // Supprimer les colonnes si nécessaire
            if (!empty($columns)) {
                Schema::table('esbtp_notes', function (Blueprint $table) use ($columns) {
                    $table->dropColumn($columns);
                });
            }

            // Modifier le champ note si la colonne existe
            if (Schema::hasColumn('esbtp_notes', 'note')) {
                Schema::table('esbtp_notes', function (Blueprint $table) {
                    $table->decimal('note', 8, 2)->change();
                });
            }
        }
    }
};
