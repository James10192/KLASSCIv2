<?php

namespace App\Http\Resources\AcademicPilotage;

use App\Domain\AcademicPilotage\Enums\GradeSheetEntryStatus;
use App\Domain\AcademicPilotage\Models\GradeSheetEntry;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPNote;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class GradeSheetResource extends JsonResource
{
    /** @var array<int, array<string, mixed>> */
    private array $allowedActions = [];

    /** @var array<int, string> */
    private array $documentDownloadUrls = [];

    /** @param array<int, array<string, mixed>> $allowedActions */
    public function withAllowedActions(array $allowedActions): self
    {
        $this->allowedActions = $allowedActions;

        return $this;
    }

    /** @param array<int, string> $documentDownloadUrls */
    public function withDocumentDownloadUrls(array $documentDownloadUrls): self
    {
        $this->documentDownloadUrls = $documentDownloadUrls;

        return $this;
    }

    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'evaluation_id' => $this->evaluation_id,
            'classe_id' => $this->classe_id,
            'matiere_id' => $this->matiere_id,
            'annee_universitaire_id' => $this->annee_universitaire_id,
            'teacher_id' => $this->teacher_id,
            'assigned_processor_id' => $this->assigned_processor_id,
            'classe' => $this->whenLoaded('classe', fn (): ?string => $this->classe
                ? trim(($this->classe->code ? $this->classe->code.' · ' : '').$this->classe->name)
                : null),
            'matiere' => $this->whenLoaded('matiere', fn (): ?string => $this->matiere?->name ?? $this->matiere?->code),
            'teacher' => $this->whenLoaded('teacher', fn (): ?string => $this->teacher?->name),
            'assigned_processor' => $this->whenLoaded('assignedProcessor', fn (): ?string => $this->assignedProcessor?->name),
            'academic_system' => $this->academic_system,
            'semester' => $this->semester,
            'evaluation_type' => $this->evaluation_type,
            'entry_mode' => $this->entry_mode?->value,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'expected_at' => $this->expected_at?->toIso8601String(),
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'received_at' => $this->received_at?->toIso8601String(),
            'entry_started_at' => $this->entry_started_at?->toIso8601String(),
            'entered_at' => $this->entered_at?->toIso8601String(),
            'controlled_at' => $this->controlled_at?->toIso8601String(),
            'validated_at' => $this->validated_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'source' => $this->source,
            'observations' => $this->observations,
            'metadata' => $this->metadata,
            'lock_version' => $this->lock_version,
            'allowed_actions' => $this->allowedActions,
            'progress' => $this->when(
                $this->resource->relationLoaded('entries'),
                fn (): array => $this->progress(),
            ),
            'entries' => $this->when(
                $this->resource->relationLoaded('entries'),
                fn (): array => $this->entries(),
            ),
            'documents' => $this->when(
                $this->resource->relationLoaded('documents'),
                fn (): array => $this->documents(),
            ),
            'events' => $this->when(
                $this->resource->relationLoaded('events'),
                fn (): array => $this->events(),
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    private function progress(): array
    {
        $entries = $this->resource->getRelation('entries');
        $total = $entries->count();
        $resolved = $entries->filter(
            fn (GradeSheetEntry $entry): bool => $entry->status !== GradeSheetEntryStatus::EXPECTED,
        );
        $gradeEntries = $entries->filter(fn (GradeSheetEntry $entry): bool => in_array(
            $entry->status,
            [GradeSheetEntryStatus::ENTERED, GradeSheetEntryStatus::ABSENT],
            true,
        ));
        $evidenced = $gradeEntries->filter(
            fn (GradeSheetEntry $entry): bool => $entry->note_id !== null && $entry->note !== null,
        );

        return [
            'total_entries' => $total,
            'resolved_entries' => $resolved->count(),
            'pending_entries' => $total - $resolved->count(),
            'completion_pct' => $total === 0 ? 0 : round(($resolved->count() / $total) * 100, 2),
            'grade_entries' => $gradeEntries->count(),
            'evidenced_grade_entries' => $evidenced->count(),
            'missing_note_evidence' => $gradeEntries->count() - $evidenced->count(),
        ];
    }

    private function entries(): array
    {
        return $this->resource->getRelation('entries')
            ->map(fn (GradeSheetEntry $entry): array => [
                'id' => $entry->id,
                'etudiant_id' => $entry->etudiant_id,
                'student' => $this->student($entry->etudiant),
                'note_id' => $entry->note_id,
                'note_evidence' => $this->noteEvidence($entry->note),
                'status' => $entry->status?->value,
                'status_label' => $entry->status?->label(),
                'source' => $entry->source,
                'metadata' => $entry->metadata,
                'entered_by' => $this->user($entry->enteredBy),
                'validated_by' => $this->user($entry->validatedBy),
                'resolved_at' => $entry->resolved_at?->toIso8601String(),
                'created_at' => $entry->created_at?->toIso8601String(),
                'updated_at' => $entry->updated_at?->toIso8601String(),
            ])
            ->all();
    }

    private function documents(): array
    {
        return $this->resource->getRelation('documents')
            ->map(fn ($document): array => [
                'id' => $document->id,
                'original_name' => $document->original_name,
                'mime_type' => $document->mime_type,
                'size_bytes' => $document->size_bytes,
                'checksum_sha256' => $document->checksum_sha256,
                'download_url' => $this->documentDownloadUrls[$document->id] ?? null,
                'uploaded_by' => $this->user($document->uploadedBy),
                'uploaded_at' => $document->uploaded_at?->toIso8601String(),
            ])
            ->all();
    }

    private function events(): array
    {
        return $this->resource->getRelation('events')
            ->map(fn ($event): array => [
                'id' => $event->id,
                'type' => $event->event_type,
                'from_status' => $event->from_status?->value,
                'to_status' => $event->to_status?->value,
                'actor' => $this->user($event->actor),
                'reason' => $event->reason,
                'metadata' => $event->metadata,
                'occurred_at' => $event->occurred_at?->toIso8601String(),
            ])
            ->all();
    }

    private function noteEvidence(?ESBTPNote $note): ?array
    {
        if ($note === null) {
            return null;
        }

        return [
            'id' => $note->id,
            'evaluation_id' => $note->evaluation_id,
            'etudiant_id' => $note->etudiant_id,
            'note' => $note->note,
            'is_absent' => (bool) $note->is_absent,
            'commentaire' => $note->commentaire,
            'created_by' => $this->user($note->createdBy),
            'updated_by' => $this->user($note->updatedBy),
            'created_at' => $note->created_at?->toIso8601String(),
            'updated_at' => $note->updated_at?->toIso8601String(),
        ];
    }

    private function student(?ESBTPEtudiant $student): ?array
    {
        if ($student === null) {
            return null;
        }

        return [
            'id' => $student->id,
            'name' => trim($student->nom.' '.$student->prenoms),
            'matricule' => $student->matricule,
        ];
    }

    private function user(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }
}
