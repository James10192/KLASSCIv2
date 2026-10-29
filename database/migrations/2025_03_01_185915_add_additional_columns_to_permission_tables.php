<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdditionalColumnsToPermissionTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tableNames = config('permission.table_names');

        if (empty($tableNames)) {
            throw new \Exception('Error: config/permission.php not loaded. Run [php artisan config:clear] and try again.');
        }

        // Vérification et ajout des colonnes à la table permissions
        Schema::table($tableNames['permissions'], function (Blueprint $table) use ($tableNames) {
            // Vérifier si la colonne category existe déjà
            if (!Schema::hasColumn($tableNames['permissions'], 'category')) {
                $table->string('category')->nullable()->after('guard_name')->comment('Catégorie de la permission');
            }
            
            // Vérifier si la colonne description existe déjà
            if (!Schema::hasColumn($tableNames['permissions'], 'description')) {
                $table->text('description')->nullable()->after('category')->comment('Description de la permission');
            }
        });

        // Vérification et ajout des colonnes à la table roles
        Schema::table($tableNames['roles'], function (Blueprint $table) use ($tableNames) {
            // Vérifier si la colonne description existe déjà
            if (!Schema::hasColumn($tableNames['roles'], 'description')) {
                $table->text('description')->nullable()->after('guard_name')->comment('Description du rôle');
            }
            
            // Vérifier si la colonne is_default existe déjà
            if (!Schema::hasColumn($tableNames['roles'], 'is_default')) {
                $table->boolean('is_default')->default(false)->after('description')->comment('Indique si c\'est un rôle par défaut');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tableNames = config('permission.table_names');

        if (empty($tableNames)) {
            throw new \Exception('Error: config/permission.php not found and defaults could not be merged. Please publish the package configuration before proceeding, or drop the tables manually.');
        }

        // Ne pas supprimer les colonnes si la méthode down est appelée sur une base de données où 
        // les colonnes ont été ajoutées par une autre migration
        Schema::table($tableNames['permissions'], function (Blueprint $table) use ($tableNames) {
            if (Schema::hasColumn($tableNames['permissions'], 'category')) {
                $table->dropColumn('category');
            }
            if (Schema::hasColumn($tableNames['permissions'], 'description')) {
                $table->dropColumn('description');
            }
        });

        Schema::table($tableNames['roles'], function (Blueprint $table) use ($tableNames) {
            if (Schema::hasColumn($tableNames['roles'], 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn($tableNames['roles'], 'is_default')) {
                $table->dropColumn('is_default');
            }
        });
    }
}
