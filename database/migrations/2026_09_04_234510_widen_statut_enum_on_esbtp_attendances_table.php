<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute « excuse » aux valeurs admises de esbtp_attendances.statut.
 *
 * La colonne est nee le 2025-03-11 (sous le nom status) en
 * ENUM('present','absent','retard') et n'a jamais ete elargie, alors que
 * l'application ecrit « excuse » depuis longtemps : ESBTPAttendanceController
 * valide `in:present,absent,retard,excuse` (appel de classe, modification
 * d'une ligne) et une dizaine de lecteurs comptent ce statut. En mode SQL
 * strict (config/database.php), l'enregistrement d'une absence excusee est
 * refuse par MySQL ; hors mode strict, la valeur stockee est une chaine vide.
 *
 * On elargit l'enum en gardant sa nullabilite et sa valeur par defaut telles
 * qu'elles sont sur chaque instance : la migration ne change rien d'autre.
 * Ajouter une valeur en fin d'ENUM est une operation instantanee sur MySQL 8
 * et MariaDB (pas de recopie de la table).
 */
return new class extends Migration
{
    private const TABLE = 'esbtp_attendances';

    private const VALEURS = ['present', 'absent', 'retard', 'excuse'];

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE) || ! Schema::hasColumn(self::TABLE, 'statut')) {
            return;
        }

        $connection = DB::connection();
        if ($connection->getDriverName() !== 'mysql') {
            return;
        }

        $colonne = $connection->selectOne(
            'SELECT column_type, is_nullable, column_default
             FROM information_schema.columns
             WHERE table_schema = ? AND table_name = ? AND column_name = ?',
            [$connection->getDatabaseName(), self::TABLE, 'statut']
        );

        if (! $colonne) {
            return;
        }

        $type = $colonne->column_type ?? $colonne->COLUMN_TYPE ?? '';
        if (stripos($type, 'enum(') !== 0 || str_contains($type, "'excuse'")) {
            // Deja elargie, ou passee en VARCHAR par une autre voie : rien a faire.
            return;
        }

        $nullable = strtoupper((string) ($colonne->is_nullable ?? $colonne->IS_NULLABLE ?? 'NO')) === 'YES';
        $defaut = $colonne->column_default ?? $colonne->COLUMN_DEFAULT ?? null;

        $definition = sprintf(
            "ENUM(%s) %s%s",
            implode(',', array_map(fn (string $v) => "'{$v}'", self::VALEURS)),
            $nullable ? 'NULL' : 'NOT NULL',
            $defaut !== null && $defaut !== '' ? ' DEFAULT ' . $connection->getPdo()->quote($defaut) : ''
        );

        $connection->statement(sprintf('ALTER TABLE `%s` MODIFY `statut` %s', self::TABLE, $definition));

        Log::info("[migration] esbtp_attendances.statut elargie a {$definition} (ancien type : {$type}).");
    }

    public function down(): void
    {
        // Volontairement sans effet : revenir a l'enum sans « excuse » tronquerait
        // toutes les absences excusees deja enregistrees.
    }
};
