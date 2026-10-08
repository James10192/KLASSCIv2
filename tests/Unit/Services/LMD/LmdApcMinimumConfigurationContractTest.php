<?php

namespace Tests\Unit\Services\LMD;

use PHPUnit\Framework\TestCase;

class LmdApcMinimumConfigurationContractTest extends TestCase
{
    public function test_apc_minimum_is_exposed_and_frozen_in_official_documents(): void
    {
        $settings = file_get_contents(resource_path('views/esbtp/settings/index.blade.php'));
        $migration = file_get_contents(database_path('migrations/2026_10_07_172500_add_lmd_apc_minimum_setting.php'));
        $pv = file_get_contents(app_path('Domain/OfficialDocuments/Services/JuryPvSnapshotBuilder.php'));
        $transcript = file_get_contents(app_path('Domain/OfficialDocuments/Services/LmdTranscriptSnapshotBuilder.php'));

        $this->assertStringContainsString('setting_lmd_compensation_inter_ue_minimum', $settings);
        $this->assertStringContainsString('Minimum UE pour APC', $settings);
        $this->assertStringContainsString('une UE à 7,5 reste NAQ', $settings);
        $this->assertStringContainsString("'lmd_compensation_inter_ue_minimum'", $migration);
        $this->assertStringContainsString("'default_value' => '0'", $migration);
        $this->assertStringContainsString("'inter_ue_compensation_minimum' => \$this->profile->interUeCompensationMinimum()", $pv);
        $this->assertStringContainsString("'inter_ue_compensation_minimum' => \$this->profile->interUeCompensationMinimum()", $transcript);
        $this->assertStringContainsString('lmd-academic-profile-v5', $pv);
        $this->assertStringContainsString('lmd-transcript-profile-v3', $transcript);
    }
}
