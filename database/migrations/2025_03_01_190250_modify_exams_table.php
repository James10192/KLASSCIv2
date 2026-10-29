<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ModifyExamsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Si la table examens n'existe pas, nous la créons
        if (!Schema::hasTable('exams')) {
            Schema::create('exams', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->foreignId('session_id')->constrained()->onDelete('cascade');
                // Nous allons ajouter la clé étrangère vers semesters séparément
                $table->unsignedBigInteger('semester_id');
                $table->date('start_date');
                $table->date('end_date');
                $table->text('description')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Vérifions si la table semesters existe, et ajoutons la clé étrangère si c'est le cas
        if (Schema::hasTable('semesters') && Schema::hasTable('exams')) {
            // Vérifier si la contrainte de clé étrangère existe déjà
            $foreignKeyExists = false;
            
            // Dans MySQL, nous pouvons vérifier si la clé étrangère existe dans INFORMATION_SCHEMA
            try {
                $foreignKeyExists = DB::select("
                    SELECT COUNT(*) as count
                    FROM information_schema.TABLE_CONSTRAINTS 
                    WHERE CONSTRAINT_TYPE = 'FOREIGN KEY' 
                    AND TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'exams' 
                    AND CONSTRAINT_NAME = 'exams_semester_id_foreign'
                ")[0]->count > 0;
            } catch (\Exception $e) {
                // Si la requête échoue, nous supposons que la clé n'existe pas
                $foreignKeyExists = false;
            }
            
            Schema::table('exams', function (Blueprint $table) use ($foreignKeyExists) {
                // Si la contrainte de clé étrangère existe déjà, nous la supprimons d'abord
                if ($foreignKeyExists) {
                    $table->dropForeign(['semester_id']);
                }

                // Ajout de la clé étrangère seulement si la table semesters existe
                if (Schema::hasTable('semesters')) {
                    // Vérifier que la colonne a bien le format attendu
                    if (Schema::hasColumn('exams', 'semester_id')) {
                        $table->foreign('semester_id')->references('id')->on('semesters')->onDelete('cascade');
                    }
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
        // Nous ne faisons rien ici, car nous ne voulons pas supprimer la table
    }
}
