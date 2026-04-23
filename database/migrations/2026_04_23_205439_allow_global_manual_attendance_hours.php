<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Autorise la saisie manuelle d'heures "globales" (sans matière) :
 *
 *   - `matiere_id` devient NULLABLE
 *   - ajout d'une colonne générée `matiere_key` = COALESCE(matiere_id, 0)
 *     qui agit comme discriminant stable pour le UNIQUE (elle permet au
 *     moteur SQL de considérer les lignes globales comme en conflit entre
 *     elles tout en conservant le comportement historique pour les lignes
 *     par matière)
 *   - l'ancien UNIQUE (etudiant, matière, année, période) est remplacé
 *     par UNIQUE (etudiant, matière_key, année, période)
 *
 * Portabilité : les colonnes générées `STORED` sont supportées à la fois
 * par MySQL 5.7+ et MariaDB 10.2+. On évite donc les index fonctionnels
 * (supportés par MySQL 8 mais pas par MariaDB).
 *
 * Rollback safety : down() refuse de tourner tant qu'il reste des lignes
 * `matiere_id IS NULL`, sinon la remise en NOT NULL échouerait en
 * cascade ou corromprait les données.
 */
return new class extends Migration
{
    public function up(): void
    {
        // L'ancien UNIQUE backe les FK etudiant_id, matiere_id et
        // annee_universitaire_id (leurs colonnes sont leftmost dans le
        // composite). On fournit des indexes FK dédiés avant de pouvoir
        // drop le unique sans violer les contraintes InnoDB.
        $this->ensureIndexExists('manual_hours_etudiant_fk_idx', 'etudiant_id');
        $this->ensureIndexExists('manual_hours_matiere_fk_idx', 'matiere_id');
        $this->ensureIndexExists('manual_hours_annee_fk_idx', 'annee_universitaire_id');

        $this->dropIndexIfExists('manual_hours_unique');

        DB::statement('ALTER TABLE esbtp_attendance_manual_hours MODIFY matiere_id BIGINT UNSIGNED NULL');

        if (! $this->columnExists('matiere_key')) {
            DB::statement(<<<'SQL'
                ALTER TABLE esbtp_attendance_manual_hours
                ADD COLUMN matiere_key BIGINT UNSIGNED AS (COALESCE(matiere_id, 0)) STORED
            SQL);
        }

        if (! $this->indexExists('manual_hours_unique_v2')) {
            DB::statement(<<<'SQL'
                CREATE UNIQUE INDEX manual_hours_unique_v2
                ON esbtp_attendance_manual_hours (etudiant_id, matiere_key, annee_universitaire_id, periode)
            SQL);
        }
    }

    public function down(): void
    {
        $remainingGlobals = DB::table('esbtp_attendance_manual_hours')
            ->whereNull('matiere_id')
            ->whereNull('deleted_at')
            ->count();

        if ($remainingGlobals > 0) {
            throw new RuntimeException(
                "Rollback refusé : {$remainingGlobals} ligne(s) manual_hours global(es) existent encore. "
                .'Supprime ou reclasse ces lignes avant de rollback cette migration.'
            );
        }

        $this->dropIndexIfExists('manual_hours_unique_v2');

        if ($this->columnExists('matiere_key')) {
            DB::statement('ALTER TABLE esbtp_attendance_manual_hours DROP COLUMN matiere_key');
        }

        DB::statement('ALTER TABLE esbtp_attendance_manual_hours MODIFY matiere_id BIGINT UNSIGNED NOT NULL');

        Schema::table('esbtp_attendance_manual_hours', function ($table) {
            $table->unique(
                ['etudiant_id', 'matiere_id', 'annee_universitaire_id', 'periode'],
                'manual_hours_unique'
            );
        });

        // Les indexes FK dédiés deviennent redondants (le unique les back)
        // mais on les garde : ils ne coûtent presque rien et évitent un
        // nouveau round-trip de drop/re-add en cas de re-up future.
    }

    private function ensureIndexExists(string $indexName, string $column): void
    {
        if (! $this->indexExists($indexName)) {
            DB::statement("ALTER TABLE esbtp_attendance_manual_hours ADD INDEX {$indexName} ({$column})");
        }
    }

    private function dropIndexIfExists(string $indexName): void
    {
        if ($this->indexExists($indexName)) {
            DB::statement("ALTER TABLE esbtp_attendance_manual_hours DROP INDEX {$indexName}");
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

    private function columnExists(string $column): bool
    {
        $row = DB::selectOne(
            'SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS '
            .'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['esbtp_attendance_manual_hours', $column]
        );

        return ((int) $row->c) > 0;
    }
};
