<?php

namespace Tests\Unit\Services;

use App\Enums\JustificationStatus;
use App\Models\ESBTPAttendance;
use App\Services\AbsenceJustificationService;
use App\Services\NotificationService;
use PHPUnit\Framework\TestCase;

/**
 * Tests Unit isoles (sans DB) pour AbsenceJustificationService.
 *
 * Focus :
 *   - canResubmit() : logique workflow pure
 *   - les Strings de constantes (size limits, disk)
 *   - exceptions sur process avec status invalide
 *
 * Les tests d'integration (save() reel, transactions, notifications dispatch)
 * sont prevus en Feature tests (deferes au-dela de W5 — voir PR body).
 */
class AbsenceJustificationServiceTest extends TestCase
{
    private AbsenceJustificationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock NotificationService — methodes void, jamais appelees dans les tests d'unite
        $notif = $this->createMock(NotificationService::class);
        $this->service = new AbsenceJustificationService($notif);
    }

    // =====================================================================
    // canResubmit()
    // =====================================================================

    public function test_can_resubmit_when_no_justification_status(): void
    {
        $absence = $this->makeAbsence(null);
        $this->assertTrue($this->service->canResubmit($absence));
    }

    public function test_cannot_resubmit_when_pending(): void
    {
        $absence = $this->makeAbsence(JustificationStatus::PENDING);
        // PENDING n'est PAS reEditable cote etudiant (cf Enum::isEditableByStudent)
        $this->assertFalse($this->service->canResubmit($absence));
    }

    public function test_can_resubmit_when_rejected(): void
    {
        $absence = $this->makeAbsence(JustificationStatus::REJECTED);
        $this->assertTrue($this->service->canResubmit($absence));
    }

    public function test_cannot_resubmit_when_approved(): void
    {
        $absence = $this->makeAbsence(JustificationStatus::APPROVED);
        $this->assertFalse($this->service->canResubmit($absence));
    }

    // =====================================================================
    // viewDocumentSignedUrl()
    // =====================================================================

    public function test_signed_url_returns_null_when_no_document(): void
    {
        $absence = $this->makeAbsence(null);
        $absence->document_path = null;
        $this->assertNull($this->service->viewDocumentSignedUrl($absence));
    }

    // =====================================================================
    // processJustification : guards
    // =====================================================================

    public function test_process_rejects_pending_status_as_target(): void
    {
        // On peut transitionner uniquement vers APPROVED ou REJECTED.
        // PENDING comme NEW status est une erreur de programmation.
        $absence = $this->makeAbsence(JustificationStatus::PENDING);
        $admin = $this->makeUserStub();

        $this->expectException(\InvalidArgumentException::class);
        $this->service->processJustification($absence, $admin, JustificationStatus::PENDING, null);
    }

    // =====================================================================
    // Constantes
    // =====================================================================

    public function test_max_document_size_is_5mb(): void
    {
        $this->assertSame(5120, AbsenceJustificationService::MAX_DOCUMENT_SIZE_KB);
    }

    // =====================================================================
    // Helpers
    // =====================================================================

    private function makeAbsence(?JustificationStatus $status): ESBTPAttendance
    {
        $abs = new ESBTPAttendance();
        $abs->setAttribute('justification_status', $status);
        return $abs;
    }

    private function makeUserStub()
    {
        // Stub minimal : id seul, pas de DB
        return new class {
            public int $id = 1;
        };
    }
}
