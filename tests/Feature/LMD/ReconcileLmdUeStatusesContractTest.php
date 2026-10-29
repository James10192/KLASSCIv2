<?php

namespace Tests\Feature\LMD;

use Tests\TestCase;

class ReconcileLmdUeStatusesContractTest extends TestCase
{
    public function test_reconciliation_is_scoped_and_dry_run_by_default(): void
    {
        $source = file_get_contents(app_path('Console/Commands/ReconcileLmdUeStatuses.php'));

        $this->assertStringContainsString('lmd:reconcile-ue-statuses', $source);
        $this->assertStringContainsString("{--classe=", $source);
        $this->assertStringContainsString("{--annee=", $source);
        $this->assertStringContainsString("{--semestre=", $source);
        $this->assertStringContainsString("if (\$this->option('apply'))", $source);
        $this->assertStringContainsString("where('is_published', true)->count()", $source);
        $this->assertStringContainsString('->lockForUpdate()', $source);
        $this->assertStringContainsString('interUeCompensationEnabled()', $source);
        $this->assertStringContainsString('interUeCompensationMinimum()', $source);
        $this->assertStringContainsString('appliquerCompensation(', $source);
        $this->assertStringNotContainsString('genererBulletinLMD(', $source);
    }
}
