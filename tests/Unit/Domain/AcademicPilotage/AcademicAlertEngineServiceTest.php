<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\AcademicPilotage;

use App\Domain\AcademicPilotage\DTO\AcademicAlertCandidate;
use App\Domain\AcademicPilotage\Enums\AcademicAlertSeverity;
use App\Domain\AcademicPilotage\Enums\AcademicAlertStatus;
use App\Domain\AcademicPilotage\Enums\AcademicAlertType;
use App\Domain\AcademicPilotage\Models\AcademicAlert;
use App\Domain\AcademicPilotage\Models\AcademicAlertEvent;
use App\Domain\AcademicPilotage\Services\AcademicAlertEngineService;

class AcademicAlertEngineServiceTest extends AcademicPilotageDatabaseTestCase
{
    public function test_upsert_is_idempotent_by_fingerprint(): void
    {
        $service = new AcademicAlertEngineService;
        $candidate = $this->candidate();

        $first = $service->upsert($candidate);
        $second = $service->upsert($candidate);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, AcademicAlert::query()->count());
        $this->assertSame(1, AcademicAlertEvent::query()->count());
        $this->assertSame('detected', AcademicAlertEvent::query()->value('event_type'));
    }

    public function test_terminal_alert_is_reopened_when_detected_again(): void
    {
        $service = new AcademicAlertEngineService;
        $alert = $service->upsert($this->candidate());
        $service->transition($alert, AcademicAlertStatus::RESOLVED, 7, 'Données corrigées.');

        $reopened = $service->upsert($this->candidate());

        $this->assertSame($alert->id, $reopened->id);
        $this->assertSame(AcademicAlertStatus::OPEN, $reopened->fresh()->status);
        $this->assertSame(
            ['detected', 'status_changed', 'reopened'],
            AcademicAlertEvent::query()->orderBy('id')->pluck('event_type')->all()
        );
    }

    public function test_transition_requires_a_reason(): void
    {
        $service = new AcademicAlertEngineService;
        $alert = $service->upsert($this->candidate());

        $this->expectException(\InvalidArgumentException::class);

        $service->transition($alert, AcademicAlertStatus::DISMISSED, 7, ' ');
    }

    private function candidate(): AcademicAlertCandidate
    {
        return new AcademicAlertCandidate(
            type: AcademicAlertType::MISSING_GRADE,
            severity: AcademicAlertSeverity::BLOCKING,
            academicYearId: 20,
            semester: 'semestre1',
            classId: 10,
            studentId: 101,
            subjectId: null,
            teacherId: null,
            message: 'Une note attendue est manquante.',
            recommendedAction: 'Complétez la fiche ou marquez une absence.',
            metadata: ['missing_entries' => 1],
        );
    }
}
