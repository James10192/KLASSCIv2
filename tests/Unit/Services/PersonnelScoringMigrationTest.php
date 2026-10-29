<?php

namespace Tests\Unit\Services;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PersonnelScoringMigrationTest extends TestCase
{
    private const TABLE = 'esbtp_personnel_score_snapshots';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'personnel_scoring_migration_test');
        config()->set('database.connections.personnel_scoring_migration_test', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);
        DB::purge('personnel_scoring_migration_test');
    }

    public function test_up_and_down_are_safe_when_snapshot_table_is_missing(): void
    {
        $migration = $this->migration();

        $migration->up();
        $migration->down();

        $this->assertFalse(Schema::hasTable(self::TABLE));
    }

    public function test_partial_schema_is_completed_and_reversed_column_by_column(): void
    {
        Schema::create(self::TABLE, function (Blueprint $table): void {
            $table->id();
            $table->decimal('coverage', 5, 4)->nullable();
        });

        $migration = $this->migration();
        $migration->up();

        foreach (['coverage', 'confidence', 'engine_version', 'evidence_hash'] as $column) {
            $this->assertTrue(Schema::hasColumn(self::TABLE, $column));
        }

        $migration->down();

        $this->assertSame(['id'], Schema::getColumnListing(self::TABLE));
    }

    private function migration(): Migration
    {
        return require database_path(
            'migrations/2026_07_11_144431_add_academic_evidence_metadata_to_esbtp_personnel_score_snapshots_table.php'
        );
    }
}
