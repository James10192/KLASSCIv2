<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Composite index utilisé par le widget « taux de présence du jour »
 * (whereDate('date') + filter sur status/statut). Sans cet index, full table scan
 * sur ~700k rows en fin d'année (700 étudiants × 5 séances × 200 jours).
 *
 * Le code applicatif filtre sur les DEUX colonnes (status ET statut) car certains
 * tenants legacy ont la version FR (`statut`) tandis que d'autres ont l'EN (`status`).
 * Cette migration crée l'index sur la colonne PRÉSENTE en DB, et passe en no-op
 * si aucune des deux n'existe (cas exotique : tenant pré-Lot-attendance).
 */
return new class extends Migration
{
    private const INDEX_NAME = 'idx_esbtp_attendances_date_status';

    public function up()
    {
        if (! Schema::hasTable('esbtp_attendances')) {
            return;
        }

        // Détecte la colonne présente (les tenants ont l'une ou l'autre selon leur âge)
        $statusColumn = $this->detectStatusColumn();
        if ($statusColumn === null) {
            // Aucune colonne status/statut → index impossible. On log et on skip
            // proprement plutôt que de planter la migration.
            \Log::warning('[migration] esbtp_attendances : pas de colonne status ni statut, index ignoré');
            return;
        }

        // Idempotence : si l'index existe déjà (re-run), on skip
        if ($this->indexExists(self::INDEX_NAME)) {
            return;
        }

        Schema::table('esbtp_attendances', function (Blueprint $table) use ($statusColumn) {
            $table->index(['date', $statusColumn], self::INDEX_NAME);
        });
    }

    public function down()
    {
        if (! Schema::hasTable('esbtp_attendances')) {
            return;
        }

        if (! $this->indexExists(self::INDEX_NAME)) {
            return;
        }

        Schema::table('esbtp_attendances', function (Blueprint $table) {
            $table->dropIndex(self::INDEX_NAME);
        });
    }

    private function detectStatusColumn(): ?string
    {
        if (Schema::hasColumn('esbtp_attendances', 'status')) {
            return 'status';
        }
        if (Schema::hasColumn('esbtp_attendances', 'statut')) {
            return 'statut';
        }
        return null;
    }

    private function indexExists(string $indexName): bool
    {
        $connection = DB::connection();
        $database = $connection->getDatabaseName();

        $rows = $connection->select(
            'SELECT 1 FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$database, 'esbtp_attendances', $indexName]
        );

        return ! empty($rows);
    }
};
