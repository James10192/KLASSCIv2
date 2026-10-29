<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class FixFeesStatusEnum extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Vérifier si la table fees existe
        if (Schema::hasTable('fees')) {
            // Utiliser une requête SQL brute pour modifier l'enum
            DB::statement("ALTER TABLE fees MODIFY COLUMN status ENUM('active', 'inactive', 'pending') DEFAULT 'pending'");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('fees')) {
            DB::statement("ALTER TABLE fees MODIFY COLUMN status ENUM('active', 'inactive') DEFAULT 'active'");
        }
    }
}
