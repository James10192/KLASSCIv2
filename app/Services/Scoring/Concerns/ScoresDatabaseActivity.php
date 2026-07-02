<?php

namespace App\Services\Scoring\Concerns;

use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait ScoresDatabaseActivity
{
    protected function tableExists(string $table): bool
    {
        return Schema::hasTable($table);
    }

    protected function columnExists(string $table, string $column): bool
    {
        return Schema::hasColumn($table, $column);
    }

    protected function table(string $table): ?Builder
    {
        return $this->tableExists($table) ? DB::table($table) : null;
    }

    protected function countRows(string $table, CarbonInterface $start, CarbonInterface $end, ?callable $scope = null): int
    {
        $query = $this->table($table);
        if (! $query) {
            return 0;
        }

        $dateColumn = $this->dateColumn($table);
        if ($dateColumn) {
            $query->whereBetween($dateColumn, [$start->copy()->startOfDay(), $end->copy()->endOfDay()]);
        }

        if ($this->columnExists($table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        if ($scope) {
            $scope($query);
        }

        return (int) $query->count();
    }

    protected function scoreByTarget(int $actual, int $target): int
    {
        if ($target <= 0) {
            return $actual > 0 ? 100 : 0;
        }

        return max(0, min(100, (int) round(($actual / $target) * 100)));
    }

    protected function scoreByRate(int $good, int $total): int
    {
        if ($total <= 0) {
            return 0;
        }

        return max(0, min(100, (int) round(($good / $total) * 100)));
    }

    private function dateColumn(string $table): ?string
    {
        foreach (['validated_at', 'date_validation', 'date_seance', 'date_evaluation', 'date_paiement', 'created_at', 'date'] as $column) {
            if ($this->columnExists($table, $column)) {
                return $column;
            }
        }

        return null;
    }
}
