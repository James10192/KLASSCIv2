<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Aligne les bases déjà déployées sur la clé étrangère que pose désormais une
 * installation neuve.
 *
 * `2026_04_23_205439` fait passer `matiere_id` en RESTRICT avant d'ajouter la
 * colonne générée `matiere_key` — sans quoi MySQL 8 refuse le schéma (erreur
 * 1215 : la colonne de base d'une colonne générée STORED ne peut pas porter
 * ON DELETE CASCADE). Les bases de la flotte ont franchi cette migration
 * avant le correctif : elles gardent la cascade.
 *
 * Rien n'y est cassé — la cascade ne se déclenche jamais, les matières étant
 * supprimées en douceur (SoftDeletes). Mais un schéma « neuf » qui diffère
 * d'un schéma « déployé » se paie plus tard, en heures passées à chercher
 * pourquoi un comportement local ne se reproduit pas en production.
 *
 * Aucune ligne n'est touchée : c'est une contrainte qu'on repose.
 */
return new class extends Migration
{
    private const TABLE = 'esbtp_attendance_manual_hours';

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        $cle = $this->cleEtrangereMatiere();

        // Déjà alignée (installation neuve) ou clé absente : ne rien faire.
        // Cette migration doit pouvoir repasser sans conséquence.
        if ($cle === null || strtoupper((string) $cle->regle) !== 'CASCADE') {
            return;
        }

        DB::statement('ALTER TABLE '.self::TABLE.' DROP FOREIGN KEY `'.$cle->nom.'`');
        DB::statement(
            'ALTER TABLE '.self::TABLE.' '
            .'ADD CONSTRAINT `esbtp_attendance_manual_hours_matiere_id_foreign` '
            .'FOREIGN KEY (matiere_id) REFERENCES esbtp_matieres (id) ON DELETE RESTRICT'
        );
    }

    /**
     * Volontairement sans effet.
     *
     * Remettre la cascade rendrait le schéma incréable sur MySQL 8 tant que
     * `matiere_key` existe, et cela pour restaurer un comportement qui ne
     * s'exécute jamais. Un rollback qui casse plus qu'il ne répare n'en est
     * pas un.
     */
    public function down(): void
    {
    }

    private function cleEtrangereMatiere(): ?object
    {
        return DB::selectOne(
            'SELECT k.CONSTRAINT_NAME AS nom, r.DELETE_RULE AS regle '
            .'FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE k '
            .'JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS r '
            .'  ON r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA '
            .' AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME '
            .'WHERE k.TABLE_SCHEMA = DATABASE() AND k.TABLE_NAME = ? '
            .'  AND k.COLUMN_NAME = ? AND k.REFERENCED_TABLE_NAME IS NOT NULL '
            .'LIMIT 1',
            [self::TABLE, 'matiere_id']
        );
    }
};
