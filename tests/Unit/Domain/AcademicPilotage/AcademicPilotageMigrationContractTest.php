<?php

namespace Tests\Unit\Domain\AcademicPilotage;

use PHPUnit\Framework\TestCase;

class AcademicPilotageMigrationContractTest extends TestCase
{
    private const MIGRATION_PATTERN = '/../../../../database/migrations/2026_07_10_21*.php';

    public function test_migrations_fail_loudly_instead_of_skipping_existing_tables(): void
    {
        $files = $this->migrationFiles();
        $this->assertCount(8, $files);

        foreach ($files as $file) {
            $this->assertStringNotContainsString('Schema::hasTable', file_get_contents($file));
        }
    }

    public function test_grade_sheet_and_note_workflow_identities_are_unique(): void
    {
        $gradeSheets = $this->migration('210111_create_esbtp_grade_sheets_table');
        $entries = $this->migration('210124_create_esbtp_grade_sheet_entries_table');

        $this->assertStringContainsString("string('obligation_key', 64)->unique()", $gradeSheets);
        $this->assertMatchesRegularExpression(
            "/foreignId\('note_id'\)->nullable\(\)->unique\(\)/",
            preg_replace('/\s+/', '', $entries)
        );
    }

    public function test_document_identity_includes_disk_and_path(): void
    {
        $documents = $this->migration('210132_create_esbtp_grade_sheet_documents_table');

        $this->assertStringContainsString(
            "unique(['disk', 'path'], 'egsd_disk_path_unique')",
            $documents
        );
    }

    /** @return array<int, string> */
    private function migrationFiles(): array
    {
        return glob(__DIR__.self::MIGRATION_PATTERN) ?: [];
    }

    private function migration(string $name): string
    {
        $matches = array_values(array_filter(
            $this->migrationFiles(),
            fn (string $file) => str_contains($file, $name)
        ));

        $this->assertCount(1, $matches, "Migration introuvable : $name");

        return file_get_contents($matches[0]);
    }
}
