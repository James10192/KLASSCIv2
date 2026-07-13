<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\DTO\AcademicAlertCandidate;
use App\Domain\AcademicPilotage\Enums\AcademicAlertStatus;
use App\Domain\AcademicPilotage\Models\AcademicAlert;
use App\Domain\AcademicPilotage\Models\AcademicAlertEvent;
use Illuminate\Support\Facades\DB;

final class AcademicAlertEngineService
{
    private const SOURCE_VERSION = '1.0.0';

    public function upsert(AcademicAlertCandidate $candidate, ?int $actorId = null): AcademicAlert
    {
        return DB::transaction(function () use ($candidate, $actorId): AcademicAlert {
            $fingerprint = $this->fingerprint($candidate);
            $alert = AcademicAlert::query()
                ->where('fingerprint', $fingerprint)
                ->lockForUpdate()
                ->first();

            if (! $alert) {
                $alert = $this->createAlert($candidate, $fingerprint);
                $this->recordEvent($alert, 'detected', null, AcademicAlertStatus::OPEN, $actorId);

                return $alert;
            }

            $from = $alert->status;
            $alert->forceFill([
                'severity' => $candidate->severity,
                'message' => $candidate->message,
                'recommended_action' => $candidate->recommendedAction,
                'metadata' => $candidate->metadata,
                'source_version' => self::SOURCE_VERSION,
                'last_seen_at' => now(),
            ]);

            if ($from instanceof AcademicAlertStatus && $from->isTerminal()) {
                $alert->forceFill([
                    'status' => AcademicAlertStatus::OPEN,
                    'acknowledged_at' => null,
                    'acknowledged_by' => null,
                    'resolved_at' => null,
                    'resolved_by' => null,
                    'dismissed_at' => null,
                    'dismissed_by' => null,
                ]);
            }

            $alert->save();

            if ($from instanceof AcademicAlertStatus && $from->isTerminal()) {
                $this->recordEvent($alert, 'reopened', $from, AcademicAlertStatus::OPEN, $actorId);
            }

            return $alert;
        });
    }

    public function transition(
        AcademicAlert $alert,
        AcademicAlertStatus $to,
        int $actorId,
        string $reason,
    ): AcademicAlert {
        if (trim($reason) === '') {
            throw new \InvalidArgumentException('Un motif est requis pour modifier une alerte académique.');
        }

        return DB::transaction(function () use ($alert, $to, $actorId, $reason): AcademicAlert {
            $locked = AcademicAlert::query()->lockForUpdate()->findOrFail($alert->getKey());
            $from = $locked->status;
            if ($from === $to) {
                return $locked;
            }

            $payload = ['status' => $to];
            if ($to === AcademicAlertStatus::ACKNOWLEDGED) {
                $payload += ['acknowledged_at' => now(), 'acknowledged_by' => $actorId];
            }
            if ($to === AcademicAlertStatus::RESOLVED) {
                $payload += ['resolved_at' => now(), 'resolved_by' => $actorId];
            }
            if ($to === AcademicAlertStatus::DISMISSED) {
                $payload += ['dismissed_at' => now(), 'dismissed_by' => $actorId];
            }

            $locked->forceFill($payload)->save();
            $this->recordEvent($locked, 'status_changed', $from, $to, $actorId, $reason);

            return $locked;
        });
    }

    public function fingerprint(AcademicAlertCandidate $candidate): string
    {
        return hash('sha256', json_encode($candidate->scope(), JSON_THROW_ON_ERROR));
    }

    private function createAlert(AcademicAlertCandidate $candidate, string $fingerprint): AcademicAlert
    {
        $alert = new AcademicAlert([
            'fingerprint' => $fingerprint,
            'type' => $candidate->type->value,
            'severity' => $candidate->severity,
            'annee_universitaire_id' => $candidate->academicYearId,
            'semester' => $candidate->semester,
            'classe_id' => $candidate->classId,
            'etudiant_id' => $candidate->studentId,
            'matiere_id' => $candidate->subjectId,
            'teacher_id' => $candidate->teacherId,
            'entity_type' => $candidate->entityType,
            'entity_id' => $candidate->entityId,
            'message' => $candidate->message,
            'recommended_action' => $candidate->recommendedAction,
            'metadata' => $candidate->metadata,
            'source_version' => self::SOURCE_VERSION,
            'detected_at' => now(),
            'last_seen_at' => now(),
        ]);
        $alert->forceFill(['status' => AcademicAlertStatus::OPEN]);
        $alert->save();

        return $alert;
    }

    private function recordEvent(
        AcademicAlert $alert,
        string $eventType,
        ?AcademicAlertStatus $from,
        ?AcademicAlertStatus $to,
        ?int $actorId,
        ?string $reason = null,
    ): void {
        $event = new AcademicAlertEvent([
            'academic_alert_id' => $alert->getKey(),
            'event_type' => $eventType,
            'reason' => $reason,
            'metadata' => [],
            'occurred_at' => now(),
        ]);
        $event->forceFill([
            'from_status' => $from,
            'to_status' => $to,
            'actor_id' => $actorId,
        ]);
        $event->save();
    }
}
