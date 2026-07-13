<?php

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\DTO\EntrySyncResult;
use App\Domain\AcademicPilotage\Enums\GradeSheetEntryStatus;
use App\Domain\AcademicPilotage\Exceptions\AcademicPilotageException;
use App\Domain\AcademicPilotage\Models\GradeSheet;
use App\Domain\AcademicPilotage\Models\GradeSheetEntry;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNote;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ExpectedGradeSheetEntrySynchronizer
{
    public function __construct(
        private readonly GradeSheetEventRecorder $eventRecorder,
        private readonly AcademicMetricSnapshotInvalidationService $invalidation,
    ) {}

    public function sync(
        GradeSheet $sheet,
        int $expectedLockVersion,
        User $actor
    ): EntrySyncResult {
        $result = DB::transaction(function () use ($sheet, $expectedLockVersion, $actor) {
            $current = GradeSheet::query()->lockForUpdate()->findOrFail($sheet->id);
            $this->assertSynchronizable($current, $expectedLockVersion);

            $cohortIds = $this->cohortIds($current);
            $notes = $this->notesByStudent($current, $cohortIds);
            $entries = GradeSheetEntry::query()
                ->where('grade_sheet_id', $current->id)
                ->lockForUpdate()
                ->get()
                ->keyBy('etudiant_id');

            $counts = $this->syncCohort($current, $cohortIds, $notes, $entries);
            $counts['deactivated'] = $this->deactivateMissing($entries, $cohortIds);
            $newVersion = $this->bumpVersion($current, $expectedLockVersion, $actor);

            $this->eventRecorder->record(
                $current,
                'entries_synced',
                $current->status,
                $current->status,
                $actor->id,
                null,
                [...$counts, 'lock_version' => $newVersion]
            );

            return new EntrySyncResult(
                $counts['created'],
                $counts['updated'],
                $counts['deactivated'],
                $counts['unchanged'],
                $newVersion
            );
        });
        $this->invalidation->fromGradeSheet($sheet);

        return $result;
    }

    private function cohortIds(GradeSheet $sheet): Collection
    {
        return ESBTPInscription::query()
            ->where('classe_id', $sheet->classe_id)
            ->where('annee_universitaire_id', $sheet->annee_universitaire_id)
            ->where('status', 'active')
            ->where('workflow_step', 'etudiant_cree')
            ->pluck('etudiant_id')
            ->unique()
            ->values();
    }

    private function notesByStudent(GradeSheet $sheet, Collection $cohortIds): Collection
    {
        if (! $sheet->evaluation_id || $cohortIds->isEmpty()) {
            return collect();
        }

        $notes = ESBTPNote::query()
            ->where('evaluation_id', $sheet->evaluation_id)
            ->whereIn('etudiant_id', $cohortIds)
            ->orderBy('id')
            ->get(['id', 'etudiant_id', 'is_absent', 'created_by']);
        $duplicateStudentIds = $notes
            ->groupBy('etudiant_id')
            ->filter(fn (Collection $studentNotes): bool => $studentNotes->count() > 1)
            ->keys()
            ->map(fn ($studentId): int => (int) $studentId)
            ->all();

        if ($duplicateStudentIds !== []) {
            throw AcademicPilotageException::duplicateNotes($duplicateStudentIds);
        }

        return $notes->keyBy('etudiant_id');
    }

    private function syncCohort(
        GradeSheet $sheet,
        Collection $cohortIds,
        Collection $notes,
        Collection $entries
    ): array {
        $counts = ['created' => 0, 'updated' => 0, 'unchanged' => 0];

        foreach ($cohortIds as $studentId) {
            $entry = $entries->get($studentId);
            $changed = $this->syncEntry($sheet, $studentId, $entry, $notes->get($studentId));
            $key = $entry ? ($changed ? 'updated' : 'unchanged') : 'created';
            $counts[$key]++;
            $entries->forget($studentId);
        }

        return $counts;
    }

    private function syncEntry(
        GradeSheet $sheet,
        int $studentId,
        ?GradeSheetEntry $entry,
        ?ESBTPNote $note
    ): bool {
        $entry ??= new GradeSheetEntry;
        $status = $this->entryStatus($entry, $note);
        $statusChanged = $entry->exists && $entry->status !== $status;
        $metadata = array_merge($entry->metadata ?? [], ['cohort_active' => true]);
        $resolvedAt = $status === GradeSheetEntryStatus::EXPECTED
            ? null
            : ($statusChanged ? now() : ($entry->resolved_at ?? now()));
        $preserveManualResolution = $status === GradeSheetEntryStatus::EXEMPT;

        $entry->forceFill([
            'grade_sheet_id' => $sheet->id,
            'etudiant_id' => $studentId,
            'note_id' => $note?->id,
            'status' => $status->value,
            'source' => $preserveManualResolution ? $entry->source : 'cohort_sync',
            'entered_by' => $preserveManualResolution
                ? $entry->entered_by
                : $note?->created_by,
            'resolved_at' => $resolvedAt,
            'metadata' => $metadata,
        ]);
        $changed = ! $entry->exists || $entry->isDirty();
        $entry->save();

        return $changed;
    }

    private function entryStatus(
        GradeSheetEntry $entry,
        ?ESBTPNote $note
    ): GradeSheetEntryStatus {
        if ($note) {
            return $note->is_absent
                ? GradeSheetEntryStatus::ABSENT
                : GradeSheetEntryStatus::ENTERED;
        }

        return $entry->status === GradeSheetEntryStatus::EXEMPT
            ? GradeSheetEntryStatus::EXEMPT
            : GradeSheetEntryStatus::EXPECTED;
    }

    private function deactivateMissing(Collection $entries, Collection $cohortIds): int
    {
        $deactivated = 0;

        foreach ($entries as $entry) {
            if (
                $cohortIds->contains($entry->etudiant_id)
                || (
                    $entry->status === GradeSheetEntryStatus::NOT_APPLICABLE
                    && ($entry->metadata['cohort_active'] ?? null) === false
                )
            ) {
                continue;
            }

            $metadata = array_merge($entry->metadata ?? [], [
                'cohort_active' => false,
                'previous_status' => $entry->status->value,
            ]);
            $entry->forceFill([
                'status' => GradeSheetEntryStatus::NOT_APPLICABLE->value,
                'resolved_at' => now(),
                'metadata' => $metadata,
            ])->save();
            $deactivated++;
        }

        return $deactivated;
    }

    private function bumpVersion(GradeSheet $sheet, int $expectedVersion, User $actor): int
    {
        $affected = GradeSheet::query()
            ->whereKey($sheet->id)
            ->where('status', $sheet->status->value)
            ->where('lock_version', $expectedVersion)
            ->update([
                'lock_version' => $expectedVersion + 1,
                'updated_by' => $actor->id,
                'updated_at' => now(),
            ]);

        if ($affected !== 1) {
            $actualVersion = (int) GradeSheet::query()
                ->whereKey($sheet->id)
                ->value('lock_version');

            throw AcademicPilotageException::staleGradeSheet($expectedVersion, $actualVersion);
        }

        return $expectedVersion + 1;
    }

    private function assertSynchronizable(GradeSheet $sheet, int $expectedVersion): void
    {
        if (! $sheet->status->allowsEntrySynchronization()) {
            throw AcademicPilotageException::mutationNotAllowed(
                'synchroniser les entrées',
                $sheet->status,
            );
        }

        if ($sheet->lock_version !== $expectedVersion) {
            throw AcademicPilotageException::staleGradeSheet(
                $expectedVersion,
                $sheet->lock_version
            );
        }
    }
}
