<?php

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\Enums\GradeSheetEntryMode;
use App\Domain\AcademicPilotage\Enums\GradeSheetStatus;
use App\Domain\AcademicPilotage\Exceptions\AcademicPilotageException;
use App\Domain\AcademicPilotage\Models\GradeSheet;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPTeacher;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class GradeSheetFactory
{
    public function __construct(
        private readonly GradeSheetEventRecorder $eventRecorder,
    ) {}

    public function createFromEvaluation(
        ESBTPEvaluation $evaluation,
        GradeSheetEntryMode $entryMode,
        User $actor,
        ?string $expectedAt = null
    ): GradeSheet {
        $evaluation->loadMissing('classe');
        $this->assertEvaluationScope($evaluation);

        $obligationKey = hash('sha256', "evaluation:{$evaluation->id}");
        $existing = GradeSheet::query()->where('obligation_key', $obligationKey)->first();
        if ($existing) {
            return $existing;
        }

        try {
            return DB::transaction(function () use (
                $evaluation,
                $entryMode,
                $actor,
                $expectedAt,
                $obligationKey
            ) {
                $sheet = new GradeSheet;
                $sheet->forceFill($this->attributes(
                    $evaluation,
                    $entryMode,
                    $actor,
                    $expectedAt,
                    $obligationKey
                ));
                $sheet->save();

                $this->eventRecorder->record(
                    $sheet,
                    'created',
                    null,
                    GradeSheetStatus::EXPECTED,
                    $actor->id,
                    null,
                    ['source' => 'evaluation']
                );

                return $sheet->refresh();
            });
        } catch (QueryException $exception) {
            return GradeSheet::query()->where('obligation_key', $obligationKey)->first()
                ?? throw $exception;
        }
    }

    private function attributes(
        ESBTPEvaluation $evaluation,
        GradeSheetEntryMode $entryMode,
        User $actor,
        ?string $expectedAt,
        string $obligationKey
    ): array {
        $teacherId = ESBTPTeacher::query()
            ->where('user_id', $evaluation->enseignant_id)
            ->value('id');

        return [
            'code' => 'GS-'.strtoupper(substr($obligationKey, 0, 12)),
            'obligation_key' => $obligationKey,
            'evaluation_id' => $evaluation->id,
            'classe_id' => $evaluation->classe_id,
            'matiere_id' => $evaluation->matiere_id,
            'annee_universitaire_id' => $evaluation->annee_universitaire_id,
            'teacher_id' => $teacherId,
            'academic_system' => $evaluation->classe->systeme_academique ?: 'BTS',
            'semester' => (string) $evaluation->periode,
            'evaluation_type' => (string) $evaluation->type,
            'entry_mode' => $entryMode->value,
            'status' => GradeSheetStatus::EXPECTED->value,
            'expected_at' => $expectedAt ?: $evaluation->date_evaluation,
            'source' => 'evaluation',
            'lock_version' => 1,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ];
    }

    private function assertEvaluationScope(ESBTPEvaluation $evaluation): void
    {
        $requiredFields = [
            'classe_id',
            'matiere_id',
            'annee_universitaire_id',
            'periode',
        ];
        $missingFields = array_values(array_filter(
            $requiredFields,
            fn (string $field): bool => empty($evaluation->{$field})
        ));

        if ($missingFields !== []) {
            throw AcademicPilotageException::invalidEvaluationScope($missingFields);
        }
    }
}
