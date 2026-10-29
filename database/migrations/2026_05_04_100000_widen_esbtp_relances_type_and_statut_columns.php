<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE esbtp_relances MODIFY type VARCHAR(40) NOT NULL DEFAULT 'email'");
        DB::statement("ALTER TABLE esbtp_relances MODIFY statut VARCHAR(40) NOT NULL DEFAULT 'planifiee'");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE esbtp_relances MODIFY type ENUM('email', 'sms', 'courrier', 'appel') NOT NULL DEFAULT 'email'");
        DB::statement("ALTER TABLE esbtp_relances MODIFY statut ENUM('planifiee', 'envoyee', 'echec') NOT NULL DEFAULT 'planifiee'");
    }
};
