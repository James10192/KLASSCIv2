<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->indexExists('manual_hours_unique_v3')) {
            DB::statement(
                'CREATE UNIQUE INDEX manual_hours_unique_v3 '
                .'ON esbtp_attendance_manual_hours '
                .'(etudiant_id, matiere_key, classe_id, annee_universitaire_id, periode)'
            );
        }

        if ($this->indexExists('manual_hours_unique_v2')) {
            DB::statement('ALTER TABLE esbtp_attendance_manual_hours DROP INDEX manual_hours_unique_v2');
        }
    }

    public function down(): void
    {
        if ($this->hasDuplicatesForV2()) {
            throw new \RuntimeException(
                'Rollback refused: class-scoped manual hours cannot be represented by the v2 unique index.'
            );
        }

        if (! $this->indexExists('manual_hours_unique_v2')) {
            DB::statement(
                'CREATE UNIQUE INDEX manual_hours_unique_v2 '
                .'ON esbtp_attendance_manual_hours '
                .'(etudiant_id, matiere_key, annee_universitaire_id, periode)'
            );
        }

        if ($this->indexExists('manual_hours_unique_v3')) {
            DB::statement('ALTER TABLE esbtp_attendance_manual_hours DROP INDEX manual_hours_unique_v3');
        }
    }

    private function indexExists(string $indexName): bool
    {
        $row = DB::selectOne(
            'SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.STATISTICS '
            .'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
            ['esbtp_attendance_manual_hours', $indexName]
        );

        return ((int) $row->c) > 0;
    }

    private function hasDuplicatesForV2(): bool
    {
        $row = DB::selectOne(
            'SELECT COUNT(*) AS c FROM ('
            .'SELECT etudiant_id, matiere_key, annee_universitaire_id, periode '
            .'FROM esbtp_attendance_manual_hours '
            .'GROUP BY etudiant_id, matiere_key, annee_universitaire_id, periode '
            .'HAVING COUNT(*) > 1'
            .') AS duplicate_rows'
        );

        return ((int) $row->c) > 0;
    }
};
