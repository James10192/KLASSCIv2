<?php

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;

class BulletinPrintDownloadGuardTest extends TestCase
{
    public function test_background_download_checks_export_type_and_bts_lmd_print_permissions(): void
    {
        $source = file_get_contents(dirname(__DIR__, 3).'/app/Http/Controllers/ESBTPBulletinTacheController.php');
        $this->assertNotFalse($source);

        $start = strpos($source, 'public function fichier(');
        $end = strpos($source, 'private function assurerProprietaire(', $start);
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);
        $download = substr($source, $start, $end - $start);

        $this->assertStringContainsString('BulletinTache::TYPE_EXPORT', $download);
        $this->assertStringContainsString("can('bulletins.export.bulk')", $download);
        $this->assertStringContainsString('ESBTPBulletin::whereIn', $download);
        $this->assertStringContainsString('ESBTPLMDBulletin::whereIn', $download);
        $this->assertStringContainsString('filtrerExport(', $download);
        $this->assertStringContainsString('count($autorises[\'allowed_ids\']) === (int) $tache->total', $download);
    }

    public function test_individual_lmd_pdf_and_preview_use_lmd_print_right(): void
    {
        $source = file_get_contents(dirname(__DIR__, 3).'/app/Http/Controllers/ESBTPLMDBulletinController.php');
        $this->assertNotFalse($source);

        preg_match_all('/\\(int\\) \\$bulletin->id,\\s*true\\s*\\);/', $source, $matches);
        $this->assertCount(2, $matches[0]);
    }
}
