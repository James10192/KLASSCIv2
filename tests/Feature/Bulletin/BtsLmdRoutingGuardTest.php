<?php

namespace Tests\Feature\Bulletin;

use Tests\TestCase;

/**
 * Test garde-fou bulletin BTS vs LMD routing.
 *
 * PR7 chantier emploi-temps-lmd-unification : ESBTPBulletinController refuse
 * les classes LMD (must use ESBTPLMDBulletinController instead).
 *
 * 3 sites guards :
 * - generate() line ~184 (création individuel)
 * - bulk generate line ~796 (génération en masse)
 * - preview line ~1196 (preview bulletin)
 *
 * @see App\Http\Controllers\ESBTPBulletinController
 * @see docs/MASTER-PLAN-emploi-temps-lmd-unification.md PR7
 * @see .claude/rules/lmd-bts-bulletin-separation.md
 */
class BtsLmdRoutingGuardTest extends TestCase
{
    /** @test */
    public function bulletin_controller_has_lmd_guards_at_3_sites(): void
    {
        $source = file_get_contents(
            app_path('Http/Controllers/ESBTPBulletinController.php')
        );

        // Compter les abort_if LMD guards (3 sites attendus)
        $count = substr_count($source, "abort_if(");
        $lmdGuards = substr_count($source, "systeme_academique ?? '') === 'LMD'");

        $this->assertGreaterThanOrEqual(3, $lmdGuards,
            'ESBTPBulletinController doit avoir 3 guards LMD (generate + bulk + preview)'
        );
    }

    /** @test */
    public function lmd_bulletin_controller_only_accepts_lmd_classes(): void
    {
        $source = file_get_contents(
            app_path('Http/Controllers/ESBTPLMDBulletinController.php')
        );

        // ESBTPLMDBulletinController filtre deja where('systeme_academique', 'LMD')
        $this->assertStringContainsString(
            "where('systeme_academique', 'LMD')",
            $source,
            'ESBTPLMDBulletinController doit filtrer where systeme_academique=LMD'
        );
    }
}
