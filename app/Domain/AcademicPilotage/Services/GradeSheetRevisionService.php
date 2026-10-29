<?php

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\Models\GradeSheet;
use App\Domain\AcademicPilotage\Models\GradeSheetEntry;
use App\Domain\AcademicPilotage\Models\GradeSheetRevision;
use App\Domain\AcademicPilotage\Exceptions\AcademicPilotageException;
use App\Models\ESBTPNote;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class GradeSheetRevisionService
{
    public function __construct(private readonly GradeSheetRevisionSnapshotBuilder $snapshots = new GradeSheetRevisionSnapshotBuilder) {}

    public function captureValidatedRevision(GradeSheet $sheet, ?int $actorId, ?string $reason): GradeSheetRevision
    {
        return DB::transaction(function () use ($sheet, $actorId, $reason): GradeSheetRevision {
            $lockedSheet = GradeSheet::query()->lockForUpdate()->findOrFail($sheet->id);
            if ($lockedSheet->lock_version !== $sheet->lock_version) {
                throw AcademicPilotageException::staleGradeSheet(
                    $sheet->lock_version,
                    $lockedSheet->lock_version,
                );
            }

            $existing = GradeSheetRevision::query()->where('grade_sheet_id', $lockedSheet->id)->where('source_lock_version', $lockedSheet->lock_version)->first();
            if ($existing !== null) { return $existing; }
            $parent = GradeSheetRevision::query()->where('grade_sheet_id', $lockedSheet->id)->lockForUpdate()->orderByDesc('revision_number')->first();
            $this->lockRevisionEvidence($lockedSheet);
            $snapshot = $this->snapshots->build($lockedSheet, $parent?->id, $reason);
            $hash = hash('sha256', $this->canonicalJson($snapshot));
            $sameSnapshot = GradeSheetRevision::query()->where('grade_sheet_id', $lockedSheet->id)->where('snapshot_sha256', $hash)->first();
            if ($sameSnapshot !== null) { return $sameSnapshot; }
            return GradeSheetRevision::query()->create([
                'grade_sheet_id' => $lockedSheet->id, 'parent_revision_id' => $parent?->id,
                'revision_number' => ($parent?->revision_number ?? 0) + 1,
                'source_lock_version' => $lockedSheet->lock_version, 'validated_by' => $actorId,
                'validated_at' => $lockedSheet->validated_at ?? now(), 'reason' => $reason,
                'rules_version' => GradeSheetRevisionSnapshotBuilder::RULES_VERSION,
                'snapshot' => $snapshot, 'snapshot_sha256' => $hash,
            ]);
        });
    }

    public function currentValidatedRevision(GradeSheet $sheet): ?GradeSheetRevision
    {
        return GradeSheetRevision::query()->where('grade_sheet_id', $sheet->id)->orderByDesc('revision_number')->first();
    }

    /** @param array<string, mixed> $snapshot */
    private function canonicalJson(array $snapshot): string
    {
        $json = json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_UNICODE);
        if (! is_string($json)) { throw new RuntimeException('Unable to encode a grade sheet revision snapshot.'); }
        return $json;
    }

    private function lockRevisionEvidence(GradeSheet $sheet): void
    {
        $entries = GradeSheetEntry::query()
            ->where('grade_sheet_id', $sheet->id)
            ->orderBy('etudiant_id')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $noteIds = $entries->pluck('note_id')->filter()->map(fn ($id) => (int) $id)->values();
        $notes = $noteIds->isEmpty()
            ? collect()
            : ESBTPNote::query()->whereIn('id', $noteIds)->lockForUpdate()->get()->keyBy('id');

        $entries->each(fn (GradeSheetEntry $entry) => $entry->setRelation('note', $entry->note_id ? $notes->get((int) $entry->note_id) : null));
        $sheet->setRelation('entries', $entries);
    }
}
