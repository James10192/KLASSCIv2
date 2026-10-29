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
 * Portabilité : les colonnes générées `STORED` existent aussi bien sur
 * MySQL 5.7+ que sur MariaDB 10.2+. On évite donc les index fonctionnels
 * (MySQL 8 les accepte, MariaDB non).
 *
 * MySQL 8 pose en revanche une condition que MariaDB ignore : la colonne de
 * base d'une colonne générée STORED ne peut pas porter une clé étrangère
 * ON DELETE CASCADE (erreur 1215). `matiere_id` en portait une, ce qui
 * rendait le schéma incréable sur MySQL 8. Elle passe donc en RESTRICT
 * juste avant l'ajout de `matiere_key`.
 *
 * Ce n'est pas un compromis. La cascade ne se déclenchait jamais : les
 * matières sont supprimées en douceur (SoftDeletes), donc aucun DELETE ne
 * les atteint. Le seul cas où RESTRICT change quelque chose est une
 * suppression SQL manuelle — et refuser vaut mieux qu'effacer sans bruit
 * des heures d'assiduité qui alimentent les bulletins.
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

        // MySQL 8 refuse une clé étrangère ON DELETE CASCADE sur la colonne
        // de base d'une colonne générée STORED. La bascule doit donc précéder
        // l'ajout de `matiere_key`, pas le suivre : une migration posée après
        // arriverait trop tard pour une base créée de zéro.
        $this->reposerCleMatiere('RESTRICT');

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

        // La colonne générée n'existe plus : la cascade d'origine redevient
        // acceptable, et un rollback doit rendre l'état d'avant, pas un état
        // amélioré.
        $this->reposerCleMatiere('CASCADE');

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

    /**
     * Repose la clé étrangère de `matiere_id` avec l'action de suppression
     * demandée.
     *
     * Le nom de la contrainte est relu plutôt que supposé : les bases de la
     * flotte n'ont pas toutes été créées par la même version des migrations,
     * et un DROP écrit en dur échouerait sur une contrainte renommée.
     */
    private function reposerCleMatiere(string $action): void
    {
        if ($nom = $this->nomCleEtrangereMatiere()) {
            DB::statement("ALTER TABLE esbtp_attendance_manual_hours DROP FOREIGN KEY `{$nom}`");
        }

        DB::statement(
            'ALTER TABLE esbtp_attendance_manual_hours '
            .'ADD CONSTRAINT `esbtp_attendance_manual_hours_matiere_id_foreign` '
            ."FOREIGN KEY (matiere_id) REFERENCES esbtp_matieres (id) ON DELETE {$action}"
        );
    }

    private function nomCleEtrangereMatiere(): ?string
    {
        $row = DB::selectOne(
            'SELECT CONSTRAINT_NAME AS nom FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE '
            .'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? '
            .'AND REFERENCED_TABLE_NAME IS NOT NULL LIMIT 1',
            ['esbtp_attendance_manual_hours', 'matiere_id']
        );

        return $row->nom ?? null;
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
