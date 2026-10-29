<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\AcademicPilotage;

use App\Domain\AcademicPilotage\DTO\AcademicAlertCandidate;
use App\Domain\AcademicPilotage\Enums\AcademicAlertSeverity;
use App\Domain\AcademicPilotage\Enums\AcademicAlertStatus;
use App\Domain\AcademicPilotage\Enums\AcademicAlertType;
use App\Domain\AcademicPilotage\Exceptions\AcademicPilotageException;
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
        $service->transition($alert, AcademicAlertStatus::ACKNOWLEDGED, 7, 'Prise en compte.');
        $service->transition($alert->fresh(), AcademicAlertStatus::RESOLVED, 7, 'Données corrigées.');

        $reopened = $service->upsert($this->candidate());

        $this->assertSame($alert->id, $reopened->id);
        $this->assertSame(AcademicAlertStatus::OPEN, $reopened->fresh()->status);
        $this->assertSame(
            ['detected', 'status_changed', 'status_changed', 'reopened'],
            AcademicAlertEvent::query()->orderBy('id')->pluck('event_type')->all()
        );
    }

    public function test_transition_requires_a_reason(): void
    {
        $service = new AcademicAlertEngineService;
        $alert = $service->upsert($this->candidate());

        $this->expectException(AcademicPilotageException::class);

        $service->transition($alert, AcademicAlertStatus::DISMISSED, 7, ' ');
    }

    public function test_allowed_manual_transitions_change_status_and_preserve_the_reason_audit(): void
    {
        $cases = [
            [AcademicAlertStatus::OPEN, AcademicAlertStatus::ACKNOWLEDGED],
            [AcademicAlertStatus::OPEN, AcademicAlertStatus::DISMISSED],
            [AcademicAlertStatus::ACKNOWLEDGED, AcademicAlertStatus::IN_PROGRESS],
            [AcademicAlertStatus::ACKNOWLEDGED, AcademicAlertStatus::RESOLVED],
            [AcademicAlertStatus::ACKNOWLEDGED, AcademicAlertStatus::DISMISSED],
            [AcademicAlertStatus::IN_PROGRESS, AcademicAlertStatus::RESOLVED],
            [AcademicAlertStatus::IN_PROGRESS, AcademicAlertStatus::DISMISSED],
        ];
        $service = new AcademicAlertEngineService;

        foreach ($cases as $index => [$from, $to]) {
            $alert = $service->upsert($this->candidate($index + 10));
            $this->moveTo($service, $alert, $from);
            $reason = "Transition {$from->value} vers {$to->value}";

            $updated = $service->transition($alert->fresh(), $to, 7, $reason);
            $event = AcademicAlertEvent::query()
                ->where('academic_alert_id', $updated->id)
                ->latest('id')
                ->firstOrFail();

            $this->assertSame($to, $updated->status);
            $this->assertSame('status_changed', $event->event_type);
            $this->assertSame($from, $event->from_status);
            $this->assertSame($to, $event->to_status);
            $this->assertSame(7, $event->actor_id);
            $this->assertSame($reason, $event->reason);
        }
    }

    public function test_invalid_and_terminal_manual_transitions_are_rejected_with_a_stable_json_contract(): void
    {
        $service = new AcademicAlertEngineService;
        $alert = $service->upsert($this->candidate());

        try {
            $service->transition($alert, AcademicAlertStatus::IN_PROGRESS, 7, 'Transition interdite');
            $this->fail('La transition aurait du etre refusee.');
        } catch (AcademicPilotageException $exception) {
            $this->assertSame(AcademicPilotageException::INVALID_TRANSITION, $exception->errorCode);
            $this->assertSame(422, $exception->statusCode);
            $this->assertSame([
                'ok' => false,
                'error' => AcademicPilotageException::INVALID_TRANSITION,
                'message' => $exception->getMessage(),
                'details' => [
                    'from' => 'open',
                    'to' => 'in_progress',
                    'allowed_transitions' => ['acknowledged', 'dismissed'],
                ],
            ], $exception->render()->getData(true));
        }

        $this->assertSame(AcademicAlertStatus::OPEN, $alert->fresh()->status);
        $this->assertSame(1, AcademicAlertEvent::query()->count());
    }

    public function test_terminal_alerts_cannot_be_manually_transitioned(): void
    {
        $service = new AcademicAlertEngineService;

        foreach ([AcademicAlertStatus::RESOLVED, AcademicAlertStatus::DISMISSED] as $index => $terminalStatus) {
            $alert = $service->upsert($this->candidate($index + 30));
            if ($terminalStatus === AcademicAlertStatus::RESOLVED) {
                $service->transition($alert, AcademicAlertStatus::ACKNOWLEDGED, 7, 'Prise en compte');
                $alert = $service->transition($alert->fresh(), $terminalStatus, 7, 'Alerte resolue');
            } else {
                $alert = $service->transition($alert, $terminalStatus, 7, 'Alerte classee');
            }

            try {
                $service->transition($alert, AcademicAlertStatus::ACKNOWLEDGED, 7, 'Tentative interdite');
                $this->fail("Le statut terminal {$terminalStatus->value} aurait du refuser la transition.");
            } catch (AcademicPilotageException $exception) {
                $this->assertSame(AcademicPilotageException::INVALID_TRANSITION, $exception->errorCode);
                $this->assertSame([], $exception->details['allowed_transitions']);
            }

            $this->assertSame($terminalStatus, $alert->fresh()->status);
        }
    }

    public function test_scope_reconciliation_auto_resolves_only_missing_managed_alerts(): void
    {
        $service = new AcademicAlertEngineService;
        $stillDetected = $service->upsert($this->candidate(10));
        $missing = $service->upsert($this->candidate(10, AcademicAlertType::STUDENT_NO_AVERAGE));
        $outsideScope = $service->upsert($this->candidate(11, AcademicAlertType::STUDENT_NO_AVERAGE));

        $resolved = $service->resolveMissingForScope(
            10,
            20,
            'semestre1',
            [AcademicAlertType::MISSING_GRADE->value, AcademicAlertType::STUDENT_NO_AVERAGE->value],
            [$stillDetected->fingerprint],
        );

        $this->assertSame(1, $resolved);
        $this->assertSame(AcademicAlertStatus::OPEN, $stillDetected->fresh()->status);
        $this->assertSame(AcademicAlertStatus::RESOLVED, $missing->fresh()->status);
        $this->assertSame(AcademicAlertStatus::OPEN, $outsideScope->fresh()->status);
        $event = AcademicAlertEvent::query()->where('academic_alert_id', $missing->id)->latest('id')->firstOrFail();
        $this->assertSame('auto_resolved', $event->event_type);
        $this->assertNull($event->actor_id);
    }

    private function moveTo(
        AcademicAlertEngineService $service,
        AcademicAlert $alert,
        AcademicAlertStatus $status,
    ): void {
        if ($status === AcademicAlertStatus::ACKNOWLEDGED) {
            $service->transition($alert, $status, 7, 'Prise en compte');

            return;
        }

        if ($status === AcademicAlertStatus::IN_PROGRESS) {
            $service->transition($alert, AcademicAlertStatus::ACKNOWLEDGED, 7, 'Prise en compte');
            $service->transition($alert->fresh(), $status, 7, 'Traitement demarre');
        }
    }

    private function candidate(
        int $classId = 10,
        AcademicAlertType $type = AcademicAlertType::MISSING_GRADE,
    ): AcademicAlertCandidate
    {
        return new AcademicAlertCandidate(
            type: $type,
            severity: AcademicAlertSeverity::BLOCKING,
            academicYearId: 20,
            semester: 'semestre1',
            classId: $classId,
            studentId: 101,
            subjectId: null,
            teacherId: null,
            message: 'Une note attendue est manquante.',
            recommendedAction: 'Complétez la fiche ou marquez une absence.',
            metadata: ['missing_entries' => 1],
        );
    }
}
