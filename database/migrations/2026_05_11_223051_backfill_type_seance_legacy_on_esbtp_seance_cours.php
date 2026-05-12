<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Idempotent backfill: normalize legacy type_seance values.
        // Conservative: 'cours' → 'AUTRE' (not 'CM') — we cannot determine
        // the pedagogical type from a generic label.
        DB::statement("
            UPDATE esbtp_seance_cours
            SET type_seance = CASE
                WHEN type_seance IS NULL                                                     THEN 'AUTRE'
                WHEN type_seance = ''                                                        THEN 'AUTRE'
                WHEN type_seance = 'cours'                                                   THEN 'AUTRE'
                WHEN type_seance = 'examen'                                                  THEN 'EXAMEN'
                WHEN type_seance NOT IN ('CM','TD','TP','PROJET','TPE','EXAMEN','AUTRE')     THEN 'AUTRE'
                ELSE type_seance
            END
        ");
    }

    // No down() — cannot restore arbitrary legacy strings after normalization.
    public function down(): void {}
};
