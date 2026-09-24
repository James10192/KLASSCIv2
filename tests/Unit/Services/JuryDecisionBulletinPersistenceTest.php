<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Domain\OfficialDocuments\Services\JuryPvIssuanceGuard;
use App\Domain\OfficialDocuments\Services\OfficialDocumentIntegrityService;
use App\Domain\OfficialDocuments\Services\OfficialDocumentService;
use App\Domain\OfficialDocuments\Services\PvNumberSequenceService;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPLMDJury;
use App\Services\JuryDeliberationService;
use App\Services\LMD\LmdDecisionProjectionService;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\Unit\Domain\OfficialDocuments\OfficialDocumentDatabaseTestCase;

final class JuryDecisionBulletinPersistenceTest extends OfficialDocumentDatabaseTestCase
{
    private JuryDeliberationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new JuryDeliberationService(
            Mockery::mock(OfficialDocumentService::class),
            Mockery::mock(OfficialDocumentIntegrityService::class),
            Mockery::mock(JuryPvIssuanceGuard::class),
            Mockery::mock(PvNumberSequenceService::class),
            new LmdDecisionProjectionService,
        );
    }

    public function test_auto_decision_persists_the_resolved_bulletin_id(): void
    {
        $jury = $this->seedScopedJury();
        DB::table('esbtp_lmd_jury_decisions')->where('id', 1)->update(['bulletin_id' => null]);

        $updated = $this->service->appliquerDecisionsAuto($jury);

        $this->assertSame(1, $updated);
        $this->assertSame(100, DB::table('esbtp_lmd_jury_decisions')->where('id', 1)->value('bulletin_id'));
    }

    public function test_override_decision_persists_the_resolved_bulletin_id(): void
    {
        $jury = $this->seedScopedJury();
        DB::table('esbtp_lmd_jury_decisions')->where('id', 2)->update(['bulletin_id' => null]);

        $decision = $this->service->overrideDecision(
            $jury,
            ESBTPEtudiant::query()->findOrFail(11),
            'admis_sous_condition',
            'Validation jury documentee',
            'majorite',
        );

        $this->assertSame(101, $decision->bulletin_id);
        $this->assertSame(101, DB::table('esbtp_lmd_jury_decisions')->where('id', 2)->value('bulletin_id'));
    }

    private function seedScopedJury(): ESBTPLMDJury
    {
        $jury = $this->seedIssuableJury();
        DB::table('esbtp_lmd_bulletins')->where('id', 100)->update([
            'moyenne_generale' => 14,
            'credits_capitalises' => 30,
            'credits_totaux' => 30,
        ]);
        DB::table('esbtp_lmd_bulletins')->where('id', 101)->update([
            'moyenne_generale' => 9,
            'credits_capitalises' => 24,
            'credits_totaux' => 30,
        ]);

        return $jury->fresh();
    }
}
