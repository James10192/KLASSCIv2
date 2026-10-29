<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modifier l'enum pour ajouter 'merged'
        DB::statement("ALTER TABLE `esbtp_attendances` MODIFY COLUMN `call_type` ENUM('start', 'end', 'merged') NOT NULL DEFAULT 'start'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Retirer 'merged' de l'enum
        DB::statement("ALTER TABLE `esbtp_attendances` MODIFY COLUMN `call_type` ENUM('start', 'end') NOT NULL DEFAULT 'start'");
    }
};
