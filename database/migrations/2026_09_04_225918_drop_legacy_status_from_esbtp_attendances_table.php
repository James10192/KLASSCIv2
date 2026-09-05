<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Retire la colonne fantôme `esbtp_attendances.status`.
 *
 * Historique : `status` (enum present/absent/retard, DEFAULT 'present') a été
 * créée le 2025-03-11 puis renommée `statut` le 2025-03-16. Sur les instances
 * où les deux colonnes coexistent, plus personne n'écrit `status` : elle vaut
 * 'present' sur chaque ligne par défaut, et tout lecteur qui s'y fiait voyait
 * 100 % de présence. Seule `statut` fait foi.
 *
 * Avant suppression, la seule information que `status` puisse encore porter
 * est recopiée : une ligne dont `statut` est vide et dont `status` n'est PAS la
 * valeur par défaut. Tout le reste est du bruit.
 */
return new class extends Migration
{
    private const TABLE = 'esbtp_attendances';

    /** Index composite posé par 2026_04_30_135040 sur (date, status|statut). */
    private const INDEX_DATE_STATUT = 'idx_esbtp_attendances_date_status';

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE) || ! Schema::hasColumn(self::TABLE, 'status')) {
            return;
        }

        if (! Schema::hasColumn(self::TABLE, 'statut')) {
            // Instance qui n'a jamais rejoué le renommage de 2025-03-16 : `status`
            // est alors la vraie colonne, on la renomme au lieu de la détruire.
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->renameColumn('status', 'statut');
            });
            Log::info('[migration] esbtp_attendances : colonne status renommée en statut (aucune colonne statut préexistante).');

            return;
        }

        $recopiees = DB::table(self::TABLE)
            ->whereNull('statut')
            ->whereNotNull('status')
            ->where('status', '!=', 'present')
            ->update(['statut' => DB::raw('`status`')]);

        Log::info("[migration] esbtp_attendances : {$recopiees} ligne(s) recopiée(s) de status vers statut avant suppression.");

        // MySQL retire silencieusement la colonne de l'index composite (date, status)
        // et laisse un index (date) sous le même nom : on le reconstruit sur statut.
        $indexPortaitStatus = in_array('status', $this->colonnesDeLIndex(self::INDEX_DATE_STATUT), true);
        if ($indexPortaitStatus) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->dropIndex(self::INDEX_DATE_STATUT);
            });
        }

        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->dropColumn('status');
        });

        if ($indexPortaitStatus) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->index(['date', 'statut'], self::INDEX_DATE_STATUT);
            });
        }

        Log::info('[migration] esbtp_attendances : colonne legacy status supprimée.');
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::TABLE) || Schema::hasColumn(self::TABLE, 'status')) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table) {
            // Nullable et SANS valeur par défaut : c'est le DEFAULT 'present'
            // d'origine qui fabriquait de fausses présences.
            $table->enum('status', ['present', 'absent', 'retard', 'excuse'])
                ->nullable()
                ->after('statut');
        });
    }

    /**
     * Colonnes composant un index de la table, dans l'ordre (vide si absent).
     *
     * @return string[]
     */
    private function colonnesDeLIndex(string $indexName): array
    {
        $connection = DB::connection();
        if ($connection->getDriverName() !== 'mysql') {
            return [];
        }

        $rows = $connection->select(
            'SELECT column_name FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?
             ORDER BY seq_in_index',
            [$connection->getDatabaseName(), self::TABLE, $indexName]
        );

        return array_map(fn ($row) => $row->column_name ?? $row->COLUMN_NAME, $rows);
    }
};
