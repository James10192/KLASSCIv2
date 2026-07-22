<?php

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\Models\GradeSheet;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

final class GradeSheetRevisionSnapshotBuilder
{
    public const RULES_VERSION = 'grade-sheet-revision-v1';

    /** @return array<string, mixed> */
    public function build(GradeSheet $sheet, ?int $parentRevisionId, ?string $reason): array
    {
        $sheet->loadMissing(['entries.note', 'evaluation']);
        $entries = $sheet->entries->sortBy([['etudiant_id', 'asc'], ['id', 'asc']])
            ->map(fn ($entry) => $this->entry($entry))->values()->all();

        return $this->canonicalize([
            'academic_scope' => $this->scope($sheet),
            'entries' => $entries,
            'lineage' => ['parent_revision_id' => $parentRevisionId, 'revalidation_reason' => $reason],
            'rules' => ['version' => self::RULES_VERSION],
            'sheet' => $this->sheet($sheet),
            'snapshot_format' => 'grade-sheet-validation-v1',
        ]);
    }

    /** @return array<string, mixed> */
    private function scope(GradeSheet $sheet): array
    {
        return ['academic_system' => $sheet->academic_system, 'annee_universitaire_id' => $sheet->annee_universitaire_id, 'classe_id' => $sheet->classe_id, 'evaluation_id' => $sheet->evaluation_id, 'matiere_id' => $sheet->matiere_id, 'semester' => $sheet->semester];
    }

    /** @return array<string, mixed> */
    private function sheet(GradeSheet $sheet): array
    {
        return $this->attributes($sheet, ['id', 'code', 'obligation_key', 'evaluation_type', 'entry_mode', 'status', 'source', 'lock_version', 'expected_at', 'submitted_at', 'received_at', 'entry_started_at', 'entered_at', 'controlled_at', 'validated_at', 'submitted_by', 'received_by', 'entered_by', 'controlled_by', 'validated_by', 'created_by', 'updated_by', 'created_at', 'updated_at', 'metadata']);
    }

    /** @return array<string, mixed> */
    private function entry(Model $entry): array
    {
        return ['entry' => $this->attributes($entry, ['id', 'etudiant_id', 'note_id', 'status', 'source', 'entered_by', 'validated_by', 'resolved_at', 'metadata', 'created_at', 'updated_at']), 'note' => $entry->relationLoaded('note') && $entry->note !== null ? $this->attributes($entry->note, ['id', 'evaluation_id', 'etudiant_id', 'matiere_id', 'note', 'valeur', 'score', 'is_absent', 'created_by', 'updated_by', 'created_at', 'updated_at']) : null];
    }

    /** @param list<string> $keys @return array<string, mixed> */
    private function attributes(Model $model, array $keys): array
    {
        $attributes = $model->getAttributes();
        $result = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $attributes)) { $result[$key] = $this->value($attributes[$key]); }
        }
        return $result;
    }

    private function value(mixed $value): mixed
    {
        if ($value instanceof CarbonInterface) { return $value->utc()->format('Y-m-d\\TH:i:s.u\\Z'); }
        if (is_string($value) && $this->isJson($value)) { return $this->canonicalize(json_decode($value, true, 512, JSON_THROW_ON_ERROR)); }
        return $value;
    }

    private function isJson(string $value): bool { json_decode($value); return json_last_error() === JSON_ERROR_NONE; }
    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) { return $value; }
        if (array_is_list($value)) { return array_map(fn ($item) => $this->canonicalize($item), $value); }
        ksort($value, SORT_STRING);
        return array_map(fn ($item) => $this->canonicalize($item), $value);
    }
}
